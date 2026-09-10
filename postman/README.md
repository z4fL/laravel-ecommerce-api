# Postman Collection - REST API E-Commerce

This directory contains the Postman Collection and Environment for the E-Commerce REST API.

## Files

- **collection.json** - Complete Postman Collection with all API endpoints organized by role and domain
- **environment.json** - Postman Environment template with configuration variables
- **README.md** - This file

## Quick Start

### 1. Import Collection and Environment

1. Open Postman
2. Click **Import** → **Select Files**
3. Choose `collection.json`
4. Click Import again for `environment.json`
5. The collection and environment should now appear in your workspace

### 2. Configure Environment

1. In the top-right corner, select **E-commerce API Environment**
2. Click the eye icon to view environment variables
3. Configure these required variables:
   - **baseUrl**: `http://localhost:8000/api/v1` (adjust for your server)
   - **customer_token**: Leave empty initially (will be set after login)
   - **seller_token**: Leave empty initially
   - **admin_token**: Leave empty initially

### 3. Get Authentication Tokens

#### For Customer:
1. Go to **Authentication → Register a customer**
2. Fill in realistic registration data and send the request
3. Copy the `access_token` from the response
4. In the environment, paste it into `customer_token`
5. Click **Save**

#### For Seller (if you have seller account):
1. Go to **Authentication → Login**
2. Use seller credentials
3. Copy `access_token` from response
4. Set it as `seller_token` in environment

#### For Admin (if you have admin account):
1. Go to **Authentication → Login**
2. Use admin credentials
3. Copy `access_token` from response
4. Set it as `admin_token` in environment

### 4. Start Testing

All requests are organized by role:
- **Public (No Auth)** - Requires no token (browse products, register, login)
- **Authentication** - Login/logout endpoints
- **Customers (Verified)** - Requires `customer_token` and verified email
- **Sellers** - Requires `seller_token` (role: seller or admin) and verified email
- **Admin** - Requires `admin_token` (role: admin)

## Collection Organization

```
Postman Collection/
├── Public (No Auth)
│   ├── Authentication
│   │   ├── Register
│   │   ├── Login
│   │   └── Password Reset
│   ├── Categories (read-only)
│   ├── Tags (read-only)
│   ├── Products (read-only)
│   ├── Stores (read-only)
│   └── Webhooks
│
├── Customers (Verified)
│   ├── Profile
│   ├── Cart Management
│   ├── Orders
│   ├── Checkout
│   └── Shipping Addresses
│
├── Sellers (Role: seller,admin + Verified)
│   └── Store Management
│       ├── Store Info
│       ├── Products (CRUD)
│       ├── Product Images
│       └── Product Stock
│
└── Admin (Role: admin)
    ├── Categories (CRUD)
    └── Tags (CRUD)
```

## Authentication Details

### Bearer Token Authentication
All authenticated requests use Bearer token in the Authorization header:
```
Authorization: Bearer {{customer_token}}
```

The token is stored in environment variables for easy switching between roles.

### Email Verification
Some endpoints require email verification:
- **Checkout** - Requires `verified` email
- **Cart Operations** - Require `verified` email
- **Orders** - Require `verified` email
- **Seller Operations** - Require `verified` email

When you register a new account, verify your email by:
1. Checking email for verification link
2. Copy the verification URL
3. In browser, visit the verification URL, or
4. Extract the ID and hash and use **Email Verification → Verify Email** request

To resend verification email:
1. Go to **Authentication → Send Email Verification**
2. Use your `customer_token`

## Key Workflows

### Complete Shopping Experience

```
1. Register (Public)
   POST /auth/register

2. Verify Email (Public)
   GET /email/verify/{id}/{hash}

3. Login (Public)
   POST /auth/login → get token

4. Browse Products (Public)
   GET /products
   GET /products/{id}

5. Add to Cart (Verified Customer)
   POST /cart/items/{product_id}

6. View Cart (Verified Customer)
   GET /cart

7. Preview Checkout (Verified Customer)
   POST /checkout

8. Create Order (Verified Customer)
   POST /orders

9. Initiate Payment (Verified Customer)
   POST /orders/{order_id}/payment
```

### Seller Setup

```
1. Register as Customer (Public)
   POST /auth/register

2. Verify Email (Public)
   GET /email/verify/{id}/{hash}

3. Create Store (Seller Role + Verified)
   POST /store

4. Create Product (Seller Role + Verified)
   POST /store/products

5. Upload Product Image (Seller Role + Verified)
   POST /store/products/{id}/images

6. Update Product Stock (Seller Role + Verified)
   PATCH /store/products/{id}/stock
```

### Admin Management

```
1. Create Category (Admin Role)
   POST /categories

2. Create Tag (Admin Role)
   POST /tags

3. Edit Product (Admin Role)
   PATCH /categories/{id}
   PATCH /tags/{id}
```

## Request Examples

All requests in the collection include realistic example data:

### Product Creation
```json
{
  "sku": "PROD-001",
  "name": "Premium Wireless Headphones",
  "description": "High-quality noise-cancelling headphones",
  "price": 150000,
  "stock": 50,
  "category_id": 1,
  "tag_ids": [1, 2, 3],
  "status": "published"
}
```

### Order Creation
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "shipping_address_id": 1
}
```

## Image Upload (Multipart/Form-data)

For image uploads (product images):
1. Open the request: **Store Management → Products → Upload Product Image**
2. Go to **Body** tab
3. Select **form-data** mode
4. In the `image` field, click the type dropdown and change to **File**
5. Click **Select Files** and choose an image
6. Send the request

Constraints:
- Maximum file size: 3MB
- Supported formats: JPG, PNG, GIF, WebP
- Images are automatically sorted by position

## Webhook Testing (Midtrans)

The **Webhooks → Payment Webhook** endpoint is for **incoming** requests from Midtrans servers, not for client testing.

For local testing:
1. Use Midtrans Sandbox notification simulator at https://simulator.midtrans.com/
2. Or manually trigger the webhook with the example payload (development only)

The webhook payload structure is realistic based on Midtrans documentation.

## Filtering and Search

Many GET endpoints support filtering:

### Products
```
GET /products?category_id=1&tag_ids[]=2&price_min=1000&price_max=500000&search=headphones
```

### Orders
```
GET /orders?status=pending_payment&page=1&per_page=20
```

See individual endpoint documentation for available filters.

## Pagination

Endpoints that return lists use pagination:
- Default: 10 items per page
- Maximum: 100 items per page
- Query parameters: `page`, `per_page`

Example:
```
GET /products?page=2&per_page=50
```

## Error Handling

The API returns structured error responses:

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "name": ["The name field is required"],
    "email": ["The email must be a valid email address"]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthorized"
}
```

### Not Found (404)
```json
{
  "message": "Resource not found"
}
```

## Rate Limiting

The API implements rate limiting:
- **General API**: Limited per minute
- **Registration**: Limited per minute
- **Login**: Limited per minute
- **Password Reset**: Limited per minute

If you exceed the limit, you'll receive a 429 (Too Many Requests) response.

## Common Issues

### "Unauthorized" Error
- Ensure the correct token is set in the environment
- Check that the email is verified (for verified endpoints)
- Verify the user has the required role

### "Token not found" Error
- Make sure you've logged in and copied the token
- Check that the token variable is correctly set in the environment
- Ensure the environment is selected (dropdown in top-right)

### "Email not verified" Error
- Go to **Email Verification → Verify Email**
- Or click the verification link from your email
- Then retry the operation

### "Image upload failed"
- Ensure image size is less than 3MB
- Verify format is JPG, PNG, GIF, or WebP
- Check that the body is set to `form-data` mode

## Generating New Tokens

If your token expires:
1. Go to **Authentication → Login**
2. Send the request with valid credentials
3. Copy the `access_token` from response
4. Update the corresponding token variable in the environment

## Postman Scripts

Some requests include:
- **Pre-request Scripts**: Run before the request (e.g., to generate timestamps)
- **Tests**: Run after the request to validate responses

These can be viewed in the **Scripts** tab of each request.

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `baseUrl` | API base URL | `http://localhost:8000/api/v1` |
| `customer_token` | Bearer token for customer | `1\|abcd1234...` |
| `seller_token` | Bearer token for seller | `2\|efgh5678...` |
| `admin_token` | Bearer token for admin | `3\|ijkl9012...` |
| `product_id` | Sample product ID for testing | `1` |
| `order_id` | Sample order ID for testing | `1` |
| `store_id` | Sample store ID for testing | `1` |

You can add more variables as needed for testing.

## Advanced Usage

### Pre-request Script Example
If you need to modify request data dynamically:
```javascript
// Example: Generate a unique username for registration
pm.environment.set("username", "user_" + Date.now());
```

### Test Script Example
```javascript
// Validate response structure
pm.test("Response has success field", function() {
    pm.response.json().should.have.property('success');
});

// Extract token for later use
pm.test("Save token for next requests", function() {
    const response = pm.response.json();
    pm.environment.set("customer_token", response.data.access_token);
});
```

## Support

For API documentation, see:
- OpenAPI Specification: `storage/api-docs/api-docs.json`
- Swagger UI: http://localhost:8000/api/documentation (if available)

For Postman help: https://learning.postman.com/

## Notes

- All timestamps in responses are ISO 8601 format
- Prices are in Indonesian Rupiah (IDR) by default
- All endpoints use API versioning (v1)
- The collection is regularly updated to match API changes
- Test data should not be committed to production environments

---

**Generated**: 2024-2025
**API Version**: 1.0.0
**Postman Collection Version**: 2.1.0
