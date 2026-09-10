/**
 * Refine Postman Collection for Laravel E-commerce API
 * - Add proper authentication configuration
 * - Organize by role (public, customer, seller, admin)
 * - Add request documentation
 * - Handle multipart/form-data requests
 * - Configure Midtrans webhook
 * - Create environment template
 */

const fs = require('fs');
const path = require('path');

// Read the raw collection
const collectionPath = path.join(__dirname, 'collection_raw.json');
const rawCollection = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));

// Helper function to find item by name
function findItem(items, name) {
    return items.find(item => item.name === name);
}

// Helper function to recursively find item in nested structure
function findItemRecursive(items, name) {
    for (const item of items) {
        if (item.name === name) return item;
        if (item.item && item.item.length > 0) {
            const result = findItemRecursive(item.item, name);
            if (result) return result;
        }
    }
    return null;
}

// Helper to add auth header
function addBearerAuth(request, tokenVar = '{{customer_token}}') {
    if (!request.header) request.header = [];
    request.header = request.header.filter(h => h.key !== 'Authorization');
    request.header.push({
        key: 'Authorization',
        value: `Bearer ${tokenVar}`,
        type: 'string'
    });
}

// Create the refined collection structure
const refinedCollection = {
    info: {
        name: 'REST API E-commerce',
        description: 'REST API for E Commerce Portfolio Project - Postman Collection',
        schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    },
    variable: [
        {
            key: 'baseUrl',
            value: 'http://localhost:8000/api/v1',
            type: 'string'
        },
        {
            key: 'customer_token',
            value: '',
            type: 'string'
        },
        {
            key: 'seller_token',
            value: '',
            type: 'string'
        },
        {
            key: 'admin_token',
            value: '',
            type: 'string'
        },
        {
            key: 'product_id',
            value: '',
            type: 'string'
        },
        {
            key: 'order_id',
            value: '',
            type: 'string'
        },
        {
            key: 'store_id',
            value: '',
            type: 'string'
        }
    ],
    item: [],
    auth: null
};

// Organize endpoints by role
const byRole = {
    'Public (No Auth)': [],
    'Customers (Verified)': [],
    'Sellers (Role: seller,admin + Verified)': [],
    'Admin (Role: admin)': []
};

// Function to categorize requests.
//
// Rewritten against the actual route files (routes/api/v1/public.php,
// protected.php, seller.php) and RoleMiddleware.php instead of loose
// heuristics — every branch below maps 1:1 to a real middleware group.
function categorizeRequest(item) {
    const path = item.request?.url?.path || [];
    const pathStr = path.join('/');
    const method = item.request?.method || '';

    // --- Fully public / guest — no auth:api middleware at all (public.php) ---
    if (pathStr === 'auth/register' || pathStr === 'auth/login' ||
        pathStr === 'auth/forgot-password' || pathStr === 'auth/reset-password') {
        return 'Public (No Auth)';
    }
    // Signed URL, not a Bearer token — the link is clicked from an email,
    // often before the user has ever logged in.
    if (pathStr.startsWith('email/verify/')) {
        return 'Public (No Auth)';
    }
    if (pathStr.startsWith('webhook')) {
        return 'Public (No Auth)';
    }
    if (method === 'GET' && (pathStr.startsWith('categories') || pathStr.startsWith('tags') ||
        pathStr.startsWith('products') || pathStr.startsWith('stores'))) {
        return 'Public (No Auth)';
    }

    // --- auth:api only, no role / no 'verified' (protected.php top group) ---
    // Any authenticated user can call these regardless of role or
    // verification status, so a customer_token is a valid, representative
    // token for testing — they're grouped under Customers for that reason.
    if (pathStr === 'auth/logout' || pathStr === 'auth/email/verification-notification') {
        return 'Customers (Verified)';
    }

    // --- role:admin (protected.php) ---
    if ((method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE') &&
        (pathStr.startsWith('categories') || pathStr.startsWith('tags'))) {
        return 'Admin (Role: admin)';
    }
    if (pathStr.startsWith('users/')) {
        return 'Admin (Role: admin)';
    }

    // --- role:seller,admin (seller.php) ---
    // Bare "store" (get/update/delete one's OWN store) has no trailing path
    // segment, so it needs an exact match separate from the "store/..."
    // prefix used for product/image/stock management underneath it.
    // POST /store (create a store) is deliberately excluded here — that
    // route lives in protected.php with NO role requirement, so it falls
    // through to the customer bucket below.
    if (pathStr === 'store' && method !== 'POST') {
        return 'Sellers (Role: seller,admin + Verified)';
    }
    if (pathStr.startsWith('store/')) {
        return 'Sellers (Role: seller,admin + Verified)';
    }

    // --- auth:api + verified, no specific role (protected.php nested group,
    // and the separate cart group) — cart, checkout, orders,
    // shipping-addresses, me, and POST /store (create own store) ---
    return 'Customers (Verified)';
}

// Paths under the "Customers (Verified)" bucket that only need auth:api
// (any authenticated user) and do NOT actually require the 'verified'
// middleware — see protected.php's top-level group vs the nested
// ->middleware('verified') group. Declared up front since it's used by
// addAuthRequirements() during the main processing loop below.
const CUSTOMER_NO_VERIFIED_REQUIRED = new Set([
    'auth/logout',
    'auth/email/verification-notification',
]);

// Process all items from raw collection
function processItems(items) {
    const processed = [];
    for (const item of items) {
        if (item.item && item.item.length > 0) {
            // This is a folder, process its children
            processItems(item.item);
        } else if (item.request) {
            // This is a request, add it
            processed.push(item);
            const role = categorizeRequest(item);
            byRole[role].push(item);
        }
    }
}

processItems(rawCollection.item);

// Build the new structure
for (const [role, requests] of Object.entries(byRole)) {
    if (requests.length === 0) continue;

    const roleFolder = {
        name: role,
        description: getRoleDescription(role),
        item: [],
        auth: role.includes('No Auth') ? null : undefined
    };

    // Group by endpoint domain
    const byDomain = {};
    for (const request of requests) {
        const path = request.request?.url?.path || [];
        const domain = path[0] || 'Other';
        
        if (!byDomain[domain]) {
            byDomain[domain] = [];
        }
        byDomain[domain].push(request);
    }

    // Create domain folders
    for (const [domain, domainRequests] of Object.entries(byDomain)) {
        const domainFolder = {
            name: formatDomain(domain),
            item: []
        };

        for (const request of domainRequests) {
            // Add auth headers for authenticated requests
            if (!role.includes('No Auth')) {
                const tokenVar = getTokenVar(role);
                addBearerAuth(request.request, tokenVar);
                request.request.auth = null;
            }

            // Add request description with role/verification requirements
            if (!request.request.description) {
                request.request.description = {};
            }
            if (typeof request.request.description === 'string') {
                request.request.description = { content: request.request.description, type: 'text/plain' };
            }
            const reqPathStr = (request.request?.url?.path || []).join('/');
            request.request.description.content = addAuthRequirements(role, reqPathStr, request.request.description.content || '');

            domainFolder.item.push(request);
        }

        roleFolder.item.push(domainFolder);
    }

    refinedCollection.item.push(roleFolder);
}

// Write refined collection
const refinedPath = path.join(__dirname, 'collection.json');
fs.writeFileSync(refinedPath, JSON.stringify(refinedCollection, null, 2));
console.log(`✅ Refined collection written to: ${refinedPath}`);

// Create environment template
const environment = {
    id: 'ecommerce-api-env',
    name: 'E-commerce API Environment',
    values: [
        {
            key: 'baseUrl',
            value: 'http://localhost:8000/api/v1',
            enabled: true,
            type: 'string'
        },
        {
            key: 'customer_token',
            value: '',
            enabled: true,
            type: 'string'
        },
        {
            key: 'seller_token',
            value: '',
            enabled: true,
            type: 'string'
        },
        {
            key: 'admin_token',
            value: '',
            enabled: true,
            type: 'string'
        },
        {
            key: 'product_id',
            value: '',
            enabled: true,
            type: 'string'
        },
        {
            key: 'order_id',
            value: '',
            enabled: true,
            type: 'string'
        },
        {
            key: 'store_id',
            value: '',
            enabled: true,
            type: 'string'
        }
    ],
    _postman_variable_scope: 'environment',
    _postman_exported_at: new Date().toISOString(),
    _postman_exported_using: 'Postman/11.0.0'
};

const envPath = path.join(__dirname, 'environment.json');
fs.writeFileSync(envPath, JSON.stringify(environment, null, 2));
console.log(`✅ Environment template written to: ${envPath}`);

// Helper functions
function getRoleDescription(role) {
    const descriptions = {
        'Public (No Auth)': 'Public endpoints - no authentication required',
        'Customers (Verified)': 'Customer endpoints - requires authentication (some also require verified email; see per-request notes)',
        'Sellers (Role: seller,admin + Verified)': 'Seller endpoints - requires seller/admin role and verified email',
        'Admin (Role: admin)': 'Admin-only endpoints - requires admin role'
    };
    return descriptions[role] || '';
}

function getTokenVar(role) {
    if (role.includes('seller')) return '{{seller_token}}';
    if (role.includes('Admin')) return '{{admin_token}}';
    return '{{customer_token}}';
}

function formatDomain(domain) {
    const map = {
        'auth': 'Authentication',
        'cart': 'Cart',
        'categories': 'Categories',
        'tags': 'Tags',
        'products': 'Products',
        'store': 'Store Management',
        'orders': 'Orders',
        'shipping-addresses': 'Shipping Addresses',
        'checkout': 'Checkout',
        'email': 'Email Verification',
        'me': 'Profile',
        'webhook': 'Webhooks',
        'stores': 'Stores'
    };
    return map[domain] || domain.charAt(0).toUpperCase() + domain.slice(1);
}

function addAuthRequirements(role, pathStr, existing) {
    if (role === 'Public (No Auth)') return existing;
    if (role === 'Customers (Verified)') {
        if (CUSTOMER_NO_VERIFIED_REQUIRED.has(pathStr)) {
            return (existing ? existing + '\n\n' : '') + '**Auth:** Requires an authenticated user token (any role, verified or not)';
        }
        return (existing ? existing + '\n\n' : '') + '**Auth:** Requires customer token\n**Verification:** Email must be verified';
    }
    if (role === 'Sellers (Role: seller,admin + Verified)') {
        return (existing ? existing + '\n\n' : '') + '**Auth:** Requires seller or admin token\n**Verification:** Email must be verified';
    }
    if (role === 'Admin (Role: admin)') {
        return (existing ? existing + '\n\n' : '') + '**Auth:** Requires admin token';
    }
    return existing;
}

console.log('\n✅ Collection refinement complete!');
console.log('Generated files:');
console.log('  - collection.json (organized by role and domain)');
console.log('  - environment.json (environment template)');
