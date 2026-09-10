/**
 * Validate Postman Collection
 * Checks for common issues and best practices
 */

const fs = require('fs');
const path = require('path');

const collectionPath = path.join(__dirname, 'collection.json');
const environmentPath = path.join(__dirname, 'environment.json');

let errors = [];
let warnings = [];
let info = [];

// Load files
let collection, environment;

try {
    collection = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));
} catch (e) {
    console.error('❌ Collection.json is not valid JSON:', e.message);
    process.exit(1);
}

try {
    environment = JSON.parse(fs.readFileSync(environmentPath, 'utf8'));
} catch (e) {
    console.error('❌ Environment.json is not valid JSON:', e.message);
    process.exit(1);
}

// Validation functions
function checkRequest(request, path = '') {
    if (!request || !request.url) return;

    const url = request.url.path?.join('/') || '';
    const fullPath = `${path}/${url}`;

    // Check for hardcoded credentials
    if (request.body) {
        const bodyStr = JSON.stringify(request.body);
        if (bodyStr.includes('password') && bodyStr.includes('12345')) {
            warnings.push(`Weak password example in ${fullPath}: "12345"`);
        }
        if (bodyStr.includes('@example.com')) {
            info.push(`Using placeholder email in ${fullPath}`);
        }
    }

    // Check for Authorization header
    const authHeader = request.header?.find(h => h.key === 'Authorization');
    if (authHeader && authHeader.value && !authHeader.value.includes('{{')) {
        errors.push(`Hardcoded auth token in ${fullPath}`);
    }

    // Check for {{baseUrl}} in URL
    if (request.url.host && Array.isArray(request.url.host)) {
        if (!request.url.host.includes('{{baseUrl}}') && !request.url.host.some(h => h.startsWith('{{'))) {
            warnings.push(`Missing {{baseUrl}} variable in ${fullPath}: ${request.url.host.join('/')}`);
        }
    }
}

function checkItems(items, path = '') {
    if (!items || !Array.isArray(items)) return;

    for (const item of items) {
        const currentPath = path ? `${path}/${item.name}` : item.name;

        if (item.request) {
            checkRequest(item.request, currentPath);
        }

        if (item.item && Array.isArray(item.item)) {
            checkItems(item.item, currentPath);
        }
    }
}

function countRequests(items, count = 0) {
    if (!items || !Array.isArray(items)) return count;

    for (const item of items) {
        if (item.request) count++;
        if (item.item) count = countRequests(item.item, count);
    }
    return count;
}

// Run validations
console.log('🔍 Validating Postman Collection...\n');

// Collection structure
if (!collection.info) errors.push('Collection missing info section');
if (!collection.item || !Array.isArray(collection.item)) errors.push('Collection missing item array');

// Environment structure
if (!environment.values || !Array.isArray(environment.values)) errors.push('Environment missing values array');

// Check for required base variables
const requiredVars = ['baseUrl', 'customer_token', 'seller_token', 'admin_token'];
const envVarNames = environment.values?.map(v => v.key) || [];

for (const varName of requiredVars) {
    if (!envVarNames.includes(varName)) {
        errors.push(`Environment missing required variable: ${varName}`);
    }
}

// Check requests
const totalRequests = countRequests(collection.item);
info.push(`Total requests in collection: ${totalRequests}`);

checkItems(collection.item);

// Check for empty/unpopulated items
let emptyItems = 0;
function countEmptyItems(items) {
    for (const item of items) {
        if (!item.request && (!item.item || item.item.length === 0)) {
            emptyItems++;
        }
        if (item.item) countEmptyItems(item.item);
    }
}
countEmptyItems(collection.item);

if (emptyItems > 0) {
    warnings.push(`Found ${emptyItems} empty folders/items`);
}

// Display results
console.log('📊 VALIDATION RESULTS\n');

if (errors.length > 0) {
    console.log('❌ ERRORS:');
    errors.forEach(err => console.log(`   - ${err}`));
    console.log();
}

if (warnings.length > 0) {
    console.log('⚠️  WARNINGS:');
    warnings.forEach(warn => console.log(`   - ${warn}`));
    console.log();
}

if (info.length > 0) {
    console.log('ℹ️  INFO:');
    info.forEach(inf => console.log(`   - ${inf}`));
    console.log();
}

// Final result
const hasErrors = errors.length > 0;
if (hasErrors) {
    console.log('❌ Validation FAILED - Please fix the errors above');
    process.exit(1);
} else {
    console.log('✅ Validation PASSED');
    console.log('\n📦 Collection Ready for Import');
    console.log('   1. Open Postman');
    console.log('   2. Import collection.json');
    console.log('   3. Import environment.json');
    console.log('   4. Configure baseUrl and authentication tokens');
}
