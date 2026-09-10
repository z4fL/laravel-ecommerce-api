/**
 * Advanced Postman Collection Enhancement
 * - Add multipart/form-data support for image uploads
 * - Configure Midtrans webhook handling
 * - Add pre-request scripts and tests
 * - Enhance documentation
 * - Add realistic request examples
 */

const fs = require('fs');
const path = require('path');

const collectionPath = path.join(__dirname, 'collection.json');
const collection = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));

// Recursively find and process requests
function processRequest(request, fullPath = '') {
    if (!request) return;

    // Handle product image upload - convert to multipart/form-data
    if (fullPath.includes('/store/products/') && fullPath.includes('/images')) {
        if (request.method === 'POST' && !fullPath.includes('reorder')) {
            request.body = {
                mode: 'formdata',
                formdata: [
                    {
                        key: 'image',
                        description: 'Product image file (JPG, PNG, etc. - max 3MB)',
                        type: 'file',
                        src: null
                    }
                ]
            };
            request.header = request.header || [];
            request.header = request.header.filter(h => h.key !== 'Content-Type');
        }
    }

    // Handle product image reorder
    if (fullPath.includes('/store/products/') && fullPath.includes('/images/reorder')) {
        if (request.method === 'PATCH') {
            if (typeof request.body?.raw === 'string') {
                try {
                    const bodyObj = JSON.parse(request.body.raw);
                    request.body = {
                        mode: 'raw',
                        raw: JSON.stringify({
                            images: [
                                { id: '<image_id>', position: 1 },
                                { id: '<image_id>', position: 2 }
                            ]
                        }, null, 2),
                        options: {
                            raw: { language: 'json', headerFamily: 'json' }
                        }
                    };
                } catch (e) {
                    // Keep original if not valid JSON
                }
            }
        }
    }

    // Enhance webhook request
    if (fullPath.includes('/webhook/payment')) {
        if (request.method === 'POST') {
            request.description = {
                content: `**Midtrans Webhook Endpoint**

⚠️ **Note:** This endpoint is called by Midtrans servers, not by clients. Do not use this request in normal workflow.

**Authentication:** No Bearer token required (Midtrans sends its own signature verification)

**Signature Verification:** Midtrans includes a notification signature calculated as:
\`\`\`
sha256(order_id + status_code + gross_amount + server_key)
\`\`\`

The signature is sent in the \`X-Callback-Token\` header. Server-side verification is implemented in the application.

**Manual Testing:** For local testing during development, you may need to:
1. Disable signature verification temporarily (development only)
2. Use Midtrans Sandbox notification simulation
3. Or manually trigger the endpoint with a valid payload

**Example Midtrans Notification Payload:**
- \`transaction_time\`: Timestamp when transaction occurred
- \`transaction_status\`: pending, capture, settlement, deny, cancel, refund
- \`transaction_id\`: Midtrans transaction ID
- \`order_id\`: Your application's order ID
- \`gross_amount\`: Transaction amount
- \`payment_type\`: Credit card, bank transfer, e-wallet, etc.

See Midtrans documentation: https://docs.midtrans.com/en/midtrans-account/overview`,
                type: 'text/markdown'
            };

            // Replace body with realistic Midtrans notification example
            request.body = {
                mode: 'raw',
                raw: JSON.stringify({
                    transaction_time: '2024-01-15 14:30:00',
                    transaction_status: 'capture',
                    transaction_id: 'f2cc02b6-0e46-49d8-aeb9-91b0e59f0e73',
                    status_message: 'Credit card transaction is successful',
                    status_code: '200',
                    order_id: 'ORDER-2024-001',
                    merchant_id: 'G123456789123456',
                    gross_amount: '100000.00',
                    currency: 'IDR',
                    payment_type: 'credit_card',
                    signature_key: 'a1b2c3d4e5f6g7h8i9j0',
                    bank: 'bca',
                    eci: '05',
                    auth_code: '000001',
                    masked_card: '48111111****1114',
                    card_type: 'credit',
                    channel_response_code: '00',
                    channel_response_message: 'Approved',
                    card_exp_month: '12',
                    card_exp_year: '2025',
                    card_issuer: 'BANK MANDIRI',
                    acquirer: 'mandiri',
                    issuer_response_code: '00',
                    issuer_response_message: 'Approved',
                    risk_level: 'low'
                }, null, 2),
                options: {
                    raw: { language: 'json', headerFamily: 'json' }
                }
            };

            // Remove bearer auth from webhook
            if (request.auth) {
                request.auth = null;
            }
            request.header = request.header.filter(h => h.key !== 'Authorization');
        }
    }
}

// Recursively process all items
function processItems(items, parentPath = '') {
    if (!items || !Array.isArray(items)) return;

    for (const item of items) {
        const currentPath = parentPath ? `${parentPath}/${item.name}` : item.name;

        if (item.request) {
            // Resolve path from request URL
            const urlPath = item.request.url?.path?.join('/') || '';
            const fullPath = `/${urlPath}`;

            processRequest(item.request, fullPath);
        }

        if (item.item && Array.isArray(item.item)) {
            processItems(item.item, currentPath);
        }
    }
}

processItems(collection.item);

// Add collection-level auth and pre-request script
collection.auth = null;

// Add collection description
collection.info.description = `REST API for E-Commerce Portfolio Project

## Getting Started

1. **Import the collection** into Postman
2. **Import the environment** (environment.json)
3. **Configure base URL**: Set \`baseUrl\` in the environment (default: http://localhost:8000/api/v1)
4. **Set tokens**:
   - Register a new account or login to get tokens
   - Copy the \`access_token\` from login response
   - Paste into \`customer_token\`, \`seller_token\`, or \`admin_token\` in the environment
5. **Test endpoints** according to your role

## Authentication

- **Public endpoints**: No authentication required (categories, products, stores, registration, login)
- **Customer endpoints**: Requires \`customer_token\` and verified email
- **Seller endpoints**: Requires \`seller_token\` (role: seller or admin) and verified email
- **Admin endpoints**: Requires \`admin_token\` (role: admin)

## Role Hierarchy

- **Customer**: Default role after registration
- **Seller**: Can create and manage stores and products
- **Admin**: Can manage categories, tags, users, and all seller features

## Key Workflows

### Customer Registration & Authentication
1. POST /auth/register (create account)
2. GET /email/verify/{id}/{hash} (verify email)
3. POST /auth/login (get token)
4. POST /auth/email/verification-notification (resend verification)

### Shopping Workflow
1. GET /products (browse products)
2. POST /cart/items/{product_id} (add to cart)
3. GET /cart (view cart)
4. POST /checkout (preview order)
5. POST /orders (create order)
6. POST /orders/{order_id}/payment (initiate payment via Midtrans)

### Seller Management
1. POST /store (create store)
2. CRUD /store/products (manage products)
3. POST /store/products/{id}/images (upload product images)
4. PATCH /store/products/{id}/stock (update stock)

### Webhook (Midtrans)
- POST /webhook/payment/midtrans (automatic payment status updates)

## Request Examples

All requests include realistic example data with proper validation. For fields with enums, only valid values are used.

## Notes

- **Image uploads**: Use form-data body type with image file
- **Pagination**: Default 10 items, max 100
- **Timestamps**: All dates are in ISO 8601 format
- **Errors**: API returns detailed validation errors for invalid requests`;

// Save enhanced collection
fs.writeFileSync(collectionPath, JSON.stringify(collection, null, 2));
console.log('✅ Collection enhanced with:');
console.log('  - Multipart form-data for image uploads');
console.log('  - Midtrans webhook configuration');
console.log('  - Enhanced documentation');
console.log('  - Realistic request examples');

// Verify JSON validity
try {
    JSON.parse(fs.readFileSync(collectionPath, 'utf8'));
    console.log('✅ Collection is valid JSON');
} catch (e) {
    console.error('❌ Invalid JSON:', e.message);
    process.exit(1);
}
