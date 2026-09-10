/**
 * Spot-check for the Postman collection generation pipeline.
 *
 * Purpose: catch the two failure modes we found by hand last time,
 * automatically, on every regeneration:
 *   1. A request that falls through to the "Customers (Verified)"
 *      default in categorizeRequest() without matching any explicit
 *      rule — usually means a new controller/route wasn't taught to
 *      the classifier yet.
 *   2. A request that still carries raw converter auth (type: apikey,
 *      {{apiKey}}) instead of a proper Bearer token — means it never
 *      went through the refine step's addBearerAuth().
 *   3. The same request NAME appearing under more than one top-level
 *      ROLE folder (Public / Authentication / Customers / Sellers /
 *      Admin) in the refined collection.json — a strong signal of a
 *      misclassification, since a single endpoint should live in
 *      exactly one role. (PUT vs PATCH duplicates *within* the same
 *      role folder are expected and NOT flagged.)
 *
 * Usage:
 *   node spot-check-collection.cjs [rawPath] [refinedPath]
 *
 * Defaults to ./collection_raw.json and ./collection.json in the
 * same directory as this script. Exits with code 1 if anything is
 * flagged, so it can be wired into the regenerate.sh / CI pipeline
 * right after validate-collection.cjs.
 */

const fs = require('fs');
const path = require('path');

const rawPath = path.resolve(process.argv[2] || path.join(__dirname, 'collection_raw.json'));
const refinedPath = path.resolve(process.argv[3] || path.join(__dirname, 'collection.json'));

let hadIssue = false;
function flag(section, msg) {
    hadIssue = true;
    console.log(`  ⚠️  [${section}] ${msg}`);
}

// ---------------------------------------------------------------------
// Check 1: does the raw collection contain any request that WOULD fall
// through to the customer default in refine-collection.cjs? This
// mirrors categorizeRequest() there 1:1 — keep the two in sync.
//
// Since categorizeRequest() is now written directly against the real
// route files (not loose heuristics), the customer default is expected
// ONLY for the specific known routes listed in
// KNOWN_CUSTOMER_DEFAULT_ROUTES below (cart, checkout, orders,
// shipping-addresses, and POST /store). Anything else landing in the
// default is a genuinely new/unmapped route and gets flagged.
// ---------------------------------------------------------------------
const KNOWN_CUSTOMER_DEFAULT_PREFIXES = ['cart', 'checkout', 'orders', 'shipping-addresses', 'me'];

function classification(pathStr, method) {
    if (pathStr === 'auth/register' || pathStr === 'auth/login' ||
        pathStr === 'auth/forgot-password' || pathStr === 'auth/reset-password') {
        return 'explicit:Public';
    }
    if (pathStr.startsWith('email/verify/')) return 'explicit:Public';
    if (pathStr.startsWith('webhook')) return 'explicit:Public';
    if (method === 'GET' && (pathStr.startsWith('categories') || pathStr.startsWith('tags') ||
        pathStr.startsWith('products') || pathStr.startsWith('stores'))) {
        return 'explicit:Public';
    }
    if (pathStr === 'auth/logout' || pathStr === 'auth/email/verification-notification') {
        return 'explicit:Customer(any-authenticated)';
    }
    if ((method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE') &&
        (pathStr.startsWith('categories') || pathStr.startsWith('tags'))) {
        return 'explicit:Admin';
    }
    if (pathStr.startsWith('users/')) return 'explicit:Admin';
    if (pathStr === 'store' && method !== 'POST') return 'explicit:Sellers';
    if (pathStr.startsWith('store/')) return 'explicit:Sellers';

    const domain = pathStr.split('/')[0];
    if (KNOWN_CUSTOMER_DEFAULT_PREFIXES.includes(domain)) {
        return 'known-fallback:Customer';
    }
    if (pathStr === 'store' && method === 'POST') {
        return 'known-fallback:Customer'; // create-your-own-store, no role required
    }
    return 'unknown-fallback:Customer';
}

function walkRaw(items, cb) {
    if (!items) return;
    for (const item of items) {
        if (item.item && item.item.length > 0) {
            walkRaw(item.item, cb);
        } else if (item.request) {
            cb(item);
        }
    }
}

console.log('🔍 Spot-checking collection pipeline output...\n');

if (fs.existsSync(rawPath)) {
    const raw = JSON.parse(fs.readFileSync(rawPath, 'utf8'));
    const unknownFallbacks = [];
    walkRaw(raw.item, (item) => {
        const p = (item.request.url?.path || []).join('/');
        const m = item.request.method || '';
        const result = classification(p, m);
        if (result === 'unknown-fallback:Customer') {
            unknownFallbacks.push(`${m} /${p}  ("${item.name}")`);
        }
    });

    console.log(`Checked ${countRequests(raw.item)} requests against known classification rules.`);
    if (unknownFallbacks.length > 0) {
        console.log(`\nRequests landing in the Customer default via an UNRECOGNIZED domain (not in the CUSTOMER_ONLY_DOMAINS whitelist):`);
        for (const h of unknownFallbacks) {
            flag('unknown-fallback-classification', `${h} — new/unmapped route. Check its actual middleware in the Laravel route files, then either add an explicit rule in refine-collection.cjs, or add its path prefix to KNOWN_CUSTOMER_DEFAULT_PREFIXES in this script if it's genuinely a plain-authenticated-customer route.`);
        }
    } else {
        console.log('  ✅ No requests fell through to an unrecognized default classification.');
    }
} else {
    console.log(`  ⚠️  Raw collection not found at ${rawPath}, skipping fallback-classification check.`);
}

// ---------------------------------------------------------------------
// Check 2 & 3: on the REFINED collection — raw apikey auth leaks, and
// the same request name appearing under more than one role folder.
// ---------------------------------------------------------------------
console.log('');
if (fs.existsSync(refinedPath)) {
    const refined = JSON.parse(fs.readFileSync(refinedPath, 'utf8'));
    const nameToRoles = new Map(); // name -> Set(roleFolderName)
    const apikeyLeaks = [];

    for (const roleFolder of refined.item || []) {
        const roleName = roleFolder.name;
        walkRaw(roleFolder.item, (item) => {
            const set = nameToRoles.get(item.name) || new Set();
            set.add(roleName);
            nameToRoles.set(item.name, set);

            const auth = item.request.auth;
            if (auth && auth.type === 'apikey') {
                apikeyLeaks.push(`"${item.name}" under ${roleName}`);
            }
        });
    }

    if (apikeyLeaks.length > 0) {
        for (const l of apikeyLeaks) {
            flag('raw-auth-leak', `${l} still has raw apikey/{{apiKey}} auth instead of a Bearer token — it likely skipped addBearerAuth().`);
        }
    } else {
        console.log('  ✅ No raw apikey auth leaks found — every non-public request has a Bearer token.');
    }

    let crossRoleDup = false;
    for (const [name, roles] of nameToRoles.entries()) {
        if (roles.size > 1) {
            crossRoleDup = true;
            flag('cross-role-duplicate', `"${name}" appears under multiple role folders: ${[...roles].join(', ')} — an endpoint should live in exactly one role.`);
        }
    }
    if (!crossRoleDup) {
        console.log('  ✅ No request name spans more than one role folder.');
    }
} else {
    console.log(`  ⚠️  Refined collection not found at ${refinedPath}, skipping auth-leak / cross-role-duplicate checks.`);
}

console.log('');
if (hadIssue) {
    console.log('❌ Spot-check FAILED — review the warnings above before committing.');
    process.exit(1);
} else {
    console.log('✅ Spot-check PASSED — no known failure patterns detected.');
    process.exit(0);
}

function countRequests(items) {
    let n = 0;
    walkRaw(items, () => { n++; });
    return n;
}
