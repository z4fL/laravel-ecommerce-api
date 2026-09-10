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
    'Authentication': [],
    'Customers (Verified)': [],
    'Sellers (Role: seller,admin + Verified)': [],
    'Admin (Role: admin)': []
};

// Function to categorize requests
function categorizeRequest(item) {
    const path = item.request?.url?.path || [];
    const pathStr = path.join('/');
    const method = item.request?.method || '';

    // Authentication endpoints
    if (pathStr.includes('auth/register') || pathStr.includes('auth/login') || 
        pathStr.includes('auth/forgot-password') || pathStr.includes('auth/reset-password') ||
        pathStr.includes('auth/logout') || pathStr.includes('auth/email')) {
        return 'Authentication';
    }

    // Public endpoints (no auth needed)
    if (method === 'GET' && (pathStr.includes('categories') || pathStr.includes('tags') || 
        pathStr.includes('products') || pathStr.includes('stores'))) {
        return 'Public (No Auth)';
    }

    // Webhook
    if (pathStr.includes('webhook')) {
        return 'Public (No Auth)';
    }

    // Seller endpoints (store management, product management)
    if (pathStr.startsWith('store/')) {
        return 'Sellers (Role: seller,admin + Verified)';
    }

    // Admin endpoints
    if ((method === 'POST' || method === 'PATCH' || method === 'DELETE') && 
        (pathStr.includes('categories') || pathStr.includes('tags'))) {
        return 'Admin (Role: admin)';
    }

    // Customer endpoints
    return 'Customers (Verified)';
}

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
            request.request.description.content = addAuthRequirements(role, request.request.description.content || '');

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
        'Authentication': 'User authentication endpoints (register, login, logout, password reset)',
        'Customers (Verified)': 'Customer endpoints - requires verified email and authentication',
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

function addAuthRequirements(role, existing) {
    if (role === 'Public (No Auth)') return existing;
    if (role === 'Authentication') return existing;
    if (role === 'Customers (Verified)') {
        return (existing ? existing + '\n\n' : '') + '**Auth:** Requires verified customer token\n**Verification:** Email must be verified';
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
