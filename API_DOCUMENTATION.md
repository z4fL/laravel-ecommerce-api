# Laravel E-Commerce API - Comprehensive Documentation

## 1. AUTHENTICATION

### Guard Configuration
- **Guard Name**: `api`
- **Driver**: `sanctum` (Laravel Sanctum - token-based authentication)
- **Token Type**: Bearer token (generated via `createToken()`)
- **Token Format**: Plain text token with format `{user_id}|{random_token_hash}`

### Authentication Flow
1. User registers or logs in
2. Server returns `access_token` and `token_type: "Bearer"`
3. Client includes token in request: `Authorization: Bearer {access_token}`
4. Tokens are stored in `personal_access_tokens` table with user relationship

---

## 2. FORM REQUESTS & VALIDATION RULES

### Authentication Requests

#### **RegisterRequest** (POST /api/v1/auth/register)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | required, max:50 |
| username | string | required, min:3, max:50, alpha_dash, unique:users |
| email | string | required, email, max:255, unique:users (soft-delete aware) |
| password | string | required, min:8, confirmed |
| phone | string | required, max:20 |

#### **LoginRequest** (POST /api/v1/auth/login)
| Field | Type | Validation Rules |
|-------|------|------------------|
| email | string | required, email, max:255 |
| password | string | required, min:8 |

#### **ForgotPasswordRequest** (POST /api/v1/auth/forgot-password)
| Field | Type | Validation Rules |
|-------|------|------------------|
| email | string | required, email |

#### **ResetPasswordRequest** (POST /api/v1/auth/reset-password)
| Field | Type | Validation Rules |
|-------|------|------------------|
| token | string | required |
| email | string | required, email |
| password | string | required, min:8, confirmed |

#### **VerifyEmailRequest** (GET /api/v1/email/verify/{id}/{hash})
- Custom authorization using hash verification
- No body parameters
- Marks user email as verified

---

### User/Profile Requests

#### **UpdateProfileRequest** (PATCH /api/v1/me)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | sometimes, max:50 |
| username | string | sometimes, min:3, max:50, alpha_dash, unique:users (ignoring current user) |
| phone | string | sometimes, max:20 |

---

### Store Management Requests

#### **StoreUserStoreRequest** (POST /api/v1/store)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | required, min:3, max:50 |
| description | string | nullable, min:10, max:1000 |
| phone | string | required, max:20 |
| status | enum | required, StoreStatus (active, suspended, pending) |

#### **UpdateStoreRequest** (PATCH /api/v1/store)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | sometimes, min:3, max:50 |
| description | string | sometimes, nullable, min:10, max:1000 |
| phone | string | sometimes, nullable, max:20 |

---

### Product Requests

#### **StoreProductRequest** (POST /api/v1/store/products)
| Field | Type | Validation Rules |
|-------|------|------------------|
| category_id | integer | required, exists:categories.id |
| sku | string | required, max:50, unique:products |
| name | string | required, min:3, max:50 |
| description | string | nullable, min:10, max:1000 |
| price | integer | required, min:0 |
| status | enum | required, ProductStatus (draft, published) |
| stock | integer | required, min:0 |
| tag_ids | array | sometimes, array of integers |
| tag_ids.* | integer | exists:tags.id |

#### **UpdateProductRequest** (PATCH /api/v1/store/products/{store_product})
| Field | Type | Validation Rules |
|-------|------|------------------|
| category_id | integer | sometimes, exists:categories.id |
| sku | string | sometimes, max:50, unique:products (ignoring current) |
| name | string | sometimes, min:3, max:150 |
| description | string | nullable, min:10, max:1000 |
| price | integer | sometimes, min:0 |
| status | enum | sometimes, ProductStatus |
| tag_ids | array | sometimes, array of integers |
| tag_ids.* | integer | exists:tags.id |

#### **UpdateProductStockRequest** (PATCH /api/v1/store/products/{store_product}/stock)
| Field | Type | Validation Rules |
|-------|------|------------------|
| stock | integer | required, min:0 |

#### **ProductIndexRequest** (GET /api/v1/products)
| Field | Type | Validation Rules |
|-------|------|------------------|
| search | string | optional search term |
| category | string | nullable |
| tag | string | nullable |
| min_price | numeric | nullable, min:0 |
| max_price | numeric | nullable, gte:min_price |
| in_stock | boolean | nullable |
| sort | string | nullable, in: [name, -name, price, -price, created_at, -created_at, updated_at, -updated_at] |
| page | integer | optional, min:1 |
| per_page | integer | optional, min:1, max:100 (default: 10) |

#### **UploadProductImageRequest** (POST /api/v1/store/products/{store_product}/images)
| Field | Type | Validation Rules |
|-------|------|------------------|
| image | file | required, image file, max:3MB |

#### **ReorderProductImageRequest** (PATCH /api/v1/store/products/{store_product}/images/reorder)
| Field | Type | Validation Rules |
|-------|------|------------------|
| image_ids | array | required, array of integers |
| image_ids.* | integer | required, distinct, exists:product_images.id |

---

### Category Requests

#### **StoreCategoryRequest** (POST /api/v1/categories)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | required, max:50, unique:categories |
| description | string | nullable, min:10, max:1000 |

#### **UpdateCategoryRequest** (PATCH /api/v1/categories/{category})
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | sometimes, max:50, unique:categories (ignoring current) |
| description | string | sometimes, min:10, max:1000 |

---

### Tag Requests

#### **StoreTagRequest** (POST /api/v1/tags)
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | required, max:50, unique:tags |

#### **UpdateTagRequest** (PATCH /api/v1/tags/{tag})
| Field | Type | Validation Rules |
|-------|------|------------------|
| name | string | sometimes, max:50, unique:tags (ignoring current) |

---

### Cart Requests

#### **StoreCartItemRequest** (POST /api/v1/cart/items/{public_product})
| Field | Type | Validation Rules |
|-------|------|------------------|
| quantity | integer | required, min:1 |

#### **UpdateCartItemRequest** (PATCH /api/v1/cart/items/{item})
| Field | Type | Validation Rules |
|-------|------|------------------|
| quantity | integer | required, min:1 |

---

### Shipping Address Requests

#### **StoreShippingAddressRequest** (POST /api/v1/shipping-addresses)
| Field | Type | Validation Rules |
|-------|------|------------------|
| recipient_name | string | required, max:50 |
| phone | string | required, max:20, regex: /^[0-9+\s\-()]+$/ |
| label | enum | nullable, AddressLabel (rumah, kantor, kos, apartemen, toko, gudang, lainnya) |
| province | string | required, max:100 |
| city | string | required, max:100 |
| district | string | required, max:100 |
| postal_code | string | required, max:10 |
| address | string | required, max:255 |
| is_default | boolean | sometimes |

#### **UpdateShippingAddressRequest** (PATCH /api/v1/shipping-addresses/{shipping_address})
| Field | Type | Validation Rules |
|-------|------|------------------|
| recipient_name | string | sometimes, max:50 |
| phone | string | sometimes, max:20, regex: /^[0-9+\s\-()]+$/ |
| label | enum | nullable, AddressLabel |
| province | string | sometimes, max:100 |
| city | string | sometimes, max:100 |
| district | string | sometimes, max:100 |
| postal_code | string | sometimes, max:10 |
| address | string | sometimes, max:255 |

---

### Order Requests

#### **StoreOrderRequest** (POST /api/v1/orders)
| Field | Type | Validation Rules |
|-------|------|------------------|
| shipping_address_id | integer | required, exists:shipping_addresses.id (user-owned) |

#### **OrderIndexRequest** (GET /api/v1/orders)
| Field | Type | Validation Rules |
|-------|------|------------------|
| page | integer | optional, min:1 |
| per_page | integer | optional, min:1, max:100 (default: 10) |

#### **CheckoutRequest** (POST /api/v1/checkout)
| Field | Type | Validation Rules |
|-------|------|------------------|
| shipping_address_id | integer | required, exists:shipping_addresses.id |

---

## 3. CONTROLLERS & ENDPOINTS

### AuthController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `register()` | POST /api/v1/auth/register | RegisterRequest | { user: User, access_token, token_type } | ❌ | Creates user + returns token |
| `login()` | POST /api/v1/auth/login | LoginRequest | { user: User, access_token, token_type } | ❌ | Returns token on success |
| `logout()` | POST /api/v1/auth/logout | - | { message } | ✅ | Deletes current token |
| `forgotPassword()` | POST /api/v1/auth/forgot-password | ForgotPasswordRequest | { message } | ❌ | Sends reset link email |
| `resetPassword()` | POST /api/v1/auth/reset-password | ResetPasswordRequest | { message } | ❌ | Resets password |

### ProfileController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `show()` | GET /api/v1/me | - | User object | ✅ | Current user profile |
| `update()` | PATCH /api/v1/me | UpdateProfileRequest | User object | ✅ | Update own profile |
| `destroy()` | DELETE /api/v1/me | - | { message } | ✅ | Soft-delete user account |
| `restore()` | POST /api/v1/users/{user}/restore | - | { message } | ✅ Admin | Restore deleted user |

### CategoryController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/categories | - | CategoryResource[] | ❌ | List all categories |
| `store()` | POST /api/v1/categories | StoreCategoryRequest | CategoryResource | ✅ Admin | Create category |
| `show()` | GET /api/v1/categories/{category} | - | CategoryResource | ❌ | Get category |
| `update()` | PATCH /api/v1/categories/{category} | UpdateCategoryRequest | CategoryResource | ✅ Admin | Update category |
| `destroy()` | DELETE /api/v1/categories/{category} | - | { message } | ✅ Admin | Soft-delete category |
| `restore()` | POST /api/v1/categories/{category}/restore | - | { message } | ✅ Admin | Restore deleted category |

### TagController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/tags | - | TagResource[] | ❌ | List all tags |
| `store()` | POST /api/v1/tags | StoreTagRequest | TagResource | ✅ Admin | Create tag |
| `show()` | GET /api/v1/tags/{tag} | - | TagResource | ❌ | Get tag |
| `update()` | PATCH /api/v1/tags/{tag} | UpdateTagRequest | TagResource | ✅ Admin | Update tag |
| `destroy()` | DELETE /api/v1/tags/{tag} | - | { message } | ✅ Admin | Soft-delete tag |
| `restore()` | POST /api/v1/tags/{tag}/restore | - | { message } | ✅ Admin | Restore deleted tag |

### ProductController (Public)

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/products | ProductIndexRequest | Paginated ProductResource[] | ❌ | List published products with caching |
| `show()` | GET /api/v1/products/{public_product} | - | ProductResource | ❌ | Get product by slug |

### StoreProductController (Seller)

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/store/products | ProductIndexRequest | Paginated ProductResource[] | ✅ Seller | Seller's products |
| `store()` | POST /api/v1/store/products | StoreProductRequest | ProductResource | ✅ Seller | Create product |
| `show()` | GET /api/v1/store/products/{store_product} | - | ProductResource | ✅ Seller | Get seller's product |
| `update()` | PATCH /api/v1/store/products/{store_product} | UpdateProductRequest | ProductResource | ✅ Seller | Update product |
| `destroy()` | DELETE /api/v1/store/products/{store_product} | - | { message } | ✅ Seller | Soft-delete product |
| `restore()` | POST /api/v1/store/products/{restore_product}/restore | - | { message } | ✅ Seller | Restore deleted product |

### StoreController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/stores | - | StoreResource[] | ❌ | List all stores |
| `show()` | GET /api/v1/stores/{store} | - | StoreResource | ❌ | Get store details |
| `showProducts()` | GET /api/v1/stores/{store}/products | ProductIndexRequest | Paginated ProductResource[] | ❌ | Get store's products |
| `store()` | POST /api/v1/store | StoreUserStoreRequest | StoreResource | ✅ User | Create store (verified email required) |
| `me()` | GET /api/v1/store | - | StoreResource | ✅ Seller | Get own store |
| `update()` | PATCH /api/v1/store | UpdateStoreRequest | StoreResource | ✅ Seller | Update own store |
| `destroy()` | DELETE /api/v1/store | - | { message } | ✅ Seller | Delete own store |

### ProductImageController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/store/products/{product}/images | - | ProductImageResource[] | ✅ Seller | Get product images |
| `store()` | POST /api/v1/store/products/{store_product}/images | UploadProductImageRequest | ProductImageResource | ✅ Seller | Upload product image (3MB max) |
| `reorder()` | PATCH /api/v1/store/products/{store_product}/images/reorder | ReorderProductImageRequest | - | ✅ Seller | Reorder images by sort order |
| `destroy()` | DELETE /api/v1/store/products/{store_product}/images/{image} | - | { message } | ✅ Seller | Delete product image |

### ProductStockController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `update()` | PATCH /api/v1/store/products/{store_product}/stock | UpdateProductStockRequest | ProductResource | ✅ Seller | Update product stock |

### CartController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `show()` | GET /api/v1/cart | - | CartResource | ✅ Verified | Get current cart |
| `destroy()` | DELETE /api/v1/cart | - | { message } | ✅ Verified | Clear entire cart |

### CartItemController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `store()` | POST /api/v1/cart/items/{public_product} | StoreCartItemRequest | CartItemResource | ✅ Verified | Add/increment product in cart |
| `update()` | PATCH /api/v1/cart/items/{item} | UpdateCartItemRequest | CartItemResource | ✅ Verified | Update cart item quantity |
| `destroy()` | DELETE /api/v1/cart/items/{item} | - | { message } | ✅ Verified | Remove item from cart |

### ShippingAddressController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/shipping-addresses | - | ShippingAddressResource[] | ✅ | List user's addresses |
| `store()` | POST /api/v1/shipping-addresses | StoreShippingAddressRequest | ShippingAddressResource | ✅ | Create address |
| `show()` | GET /api/v1/shipping-addresses/{shipping_address} | - | ShippingAddressResource | ✅ | Get address |
| `update()` | PATCH /api/v1/shipping-addresses/{shipping_address} | UpdateShippingAddressRequest | ShippingAddressResource | ✅ | Update address |
| `destroy()` | DELETE /api/v1/shipping-addresses/{shipping_address} | - | { message } | ✅ | Delete address |
| `makeDefault()` | PATCH /api/v1/shipping-addresses/{shipping_address}/default | - | ShippingAddressResource | ✅ | Set as default address |

### OrderController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `index()` | GET /api/v1/orders | OrderIndexRequest | Paginated OrderResource[] | ✅ Verified | List user's orders |
| `store()` | POST /api/v1/orders | StoreOrderRequest | OrderResource | ✅ Verified | Create order from cart |
| `show()` | GET /api/v1/orders/{order} | - | OrderResource | ✅ Verified | Get order details |
| `cancel()` | POST /api/v1/orders/{order}/cancel | - | OrderResource | ✅ Verified | Cancel order |

### CheckoutController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `preview()` | POST /api/v1/checkout | CheckoutRequest | CheckoutResource | ✅ Verified | Preview checkout before order |

### PaymentController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `store()` | POST /api/v1/orders/{order}/payment | - | PaymentResource | ✅ Verified | Create payment for order |

### EmailVerificationController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `send()` | POST /api/v1/auth/email/verification-notification | - | { message } | ✅ | Send verification email |
| `verify()` | GET /api/v1/email/verify/{id}/{hash} | - | { message } | ❌ | Verify email (signed URL) |

### WebhookController

| Method | Endpoint | Request | Response | Auth | Notes |
|--------|----------|---------|----------|------|-------|
| `handle()` | POST /api/v1/webhook/payment/{gateway} | PaymentWebhookRequest | { payment_id, outcome, transition } | ❌ | Payment webhook (Midtrans) |

---

## 4. MODELS & KEY FIELDS

### User Model
```
Fields:
- id (uuid)
- name (string)
- username (string) - unique
- email (string) - unique
- email_verified_at (datetime, nullable)
- password (string) - hashed
- phone (string)
- role (UserRole enum: admin, seller, customer)
- created_at, updated_at, deleted_at (soft delete)

Relations:
- store() HasOne Store
- cart() HasOne Cart
- addresses() HasMany ShippingAddress
- orders() HasMany Order
- tokens() HasMany PersonalAccessToken (Sanctum)
```

### Product Model
```
Fields:
- id (integer)
- store_id (foreign key)
- category_id (foreign key)
- sku (string) - unique
- name (string)
- slug (string) - unique
- description (string, nullable)
- price (integer) - in cents/smallest currency unit
- status (ProductStatus enum: draft, published)
- stock (integer)
- created_at, updated_at, deleted_at (soft delete)

Relations:
- store() BelongsTo Store
- category() BelongsTo Category
- tags() BelongsToMany Tag
- images() HasMany ProductImage
- cartItems() HasMany CartItem
- orderItems() HasMany OrderItem

Scopes:
- published() - filters to status = published
- search($term) - searches by name/description
- filter($filters) - filters by category, tag, price range, in_stock
- sort($sort) - sorts by name, price, created_at, updated_at

Features:
- Uses cache versioning for listings
- Auto-invalidates cache on create/update/delete
- Reorders images automatically
```

### Store Model
```
Fields:
- id (integer)
- user_id (foreign key)
- name (string)
- slug (string) - unique
- description (string, nullable)
- status (StoreStatus enum: active, suspended, pending)
- phone (string)
- created_at, updated_at, deleted_at (soft delete)

Relations:
- user() BelongsTo User
- products() HasMany Product
```

### Order Model
```
Fields:
- id (integer)
- order_number (string) - unique
- user_id (foreign key)
- status (OrderStatus enum: pending_payment, payment_failed, paid, processing, shipped, completed, cancelled)
- recipient_name (string)
- phone (string)
- province (string)
- city (string)
- district (string)
- postal_code (string)
- address (string)
- subtotal (integer)
- total (integer)
- created_at, updated_at

Relations:
- user() BelongsTo User
- orderItems() HasMany OrderItem
- payments() HasMany Payment
```

### Cart Model
```
Fields:
- id (integer)
- user_id (foreign key) - unique
- created_at, updated_at

Relations:
- user() BelongsTo User
- cartItems() HasMany CartItem
```

### CartItem Model
```
Fields:
- id (integer)
- cart_id (foreign key)
- product_id (foreign key)
- quantity (integer)
- price_snapshot (integer) - price at time of adding to cart
- created_at, updated_at

Relations:
- cart() BelongsTo Cart
- product() BelongsTo Product
```

### Category Model
```
Fields:
- id (integer)
- name (string) - unique
- slug (string) - unique
- description (string, nullable)
- created_at, updated_at, deleted_at (soft delete)

Relations:
- products() HasMany Product
```

### Tag Model
```
Fields:
- id (integer)
- name (string) - unique
- slug (string) - unique
- created_at, updated_at, deleted_at (soft delete)

Relations:
- products() BelongsToMany Product
```

### ShippingAddress Model
```
Fields:
- id (integer)
- user_id (foreign key)
- recipient_name (string)
- phone (string)
- label (AddressLabel enum: rumah, kantor, kos, apartemen, toko, gudang, lainnya, nullable)
- province (string)
- city (string)
- district (string)
- postal_code (string)
- address (string)
- is_default (boolean)
- created_at, updated_at

Relations:
- user() BelongsTo User
```

### ProductImage Model
```
Fields:
- id (integer)
- product_id (foreign key)
- path (string) - storage path
- sort_order (integer)
- created_at, updated_at

Relations:
- product() BelongsTo Product
```

### Payment Model
```
Fields:
- id (integer/uuid)
- order_id (foreign key)
- gateway (string) - payment gateway name (e.g., 'midtrans')
- gateway_transaction_id (string, nullable)
- payment_method (string, nullable)
- status (PaymentStatus enum: pending, paid, failed, expired, cancelled, refunded)
- amount (integer)
- payment_url (string, nullable)
- expired_at (datetime, nullable)
- paid_at (datetime, nullable)
- metadata (json) - gateway-specific data
- created_at, updated_at

Relations:
- order() BelongsTo Order
```

### OrderItem Model
```
Fields:
- id (integer)
- order_id (foreign key)
- product_id (foreign key)
- product_sku (string)
- product_name (string)
- price (integer) - price at time of order
- quantity (integer)
- subtotal (integer)
- created_at, updated_at

Relations:
- order() BelongsTo Order
- product() BelongsTo Product
```

---

## 5. MIDDLEWARE

### RoleMiddleware (app/Http/Middleware/RoleMiddleware.php)
- **Purpose**: Authorize requests based on user role
- **Usage**: `middleware('role:seller,admin')` or `middleware('role:admin')`
- **Roles**: admin, seller, customer
- **Behavior**: 
  - Checks if user is authenticated
  - Verifies user role can access required role
  - Admin can access any role
  - Seller can access seller and customer resources
  - Customer can only access customer resources
  - Throws `AuthorizationException` if not authorized

### Built-in Middleware Used
- **auth:api** - Requires Sanctum authentication
- **verified** - Requires verified email (checks `email_verified_at`)
- **guest** - User must NOT be authenticated
- **signed** - Validates signed URL (for email verification)
- **throttle** - Rate limiting:
  - `throttle:register` - Registration throttling
  - `throttle:login` - Login throttling
  - `throttle:password-reset` - Password reset throttling
  - `throttle:email-verification` - Email verification throttling
  - `throttle:api` - General API throttling

---

## 6. RESOURCES (API RESPONSE STRUCTURES)

### UserResource
```json
{
  "id": "uuid",
  "name": "string",
  "username": "string",
  "email": "string",
  "email_verified_at": "datetime|null",
  "phone": "string",
  "role": "admin|seller|customer",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### CategoryResource
```json
{
  "id": "integer",
  "name": "string",
  "slug": "string",
  "description": "string|null"
}
```

### TagResource
```json
{
  "id": "integer",
  "name": "string",
  "slug": "string"
}
```

### ProductImageResource
```json
{
  "id": "integer",
  "product_id": "integer",
  "path": "string",
  "sort_order": "integer"
}
```

### ProductResource
```json
{
  "id": "integer",
  "sku": "string",
  "name": "string",
  "slug": "string",
  "description": "string|null",
  "price": "integer",
  "status": "draft|published",
  "stock": "integer",
  "store": {
    "id": "integer",
    "name": "string"
  },
  "category": CategoryResource,
  "tags": TagResource[],
  "images": ProductImageResource[]
}
```

### StoreResource
```json
{
  "name": "string",
  "slug": "string",
  "description": "string|null",
  "status": "active|suspended|pending",
  "user": {
    "id": "integer",
    "name": "string"
  },
  "products": ProductResource[]
}
```

### CartItemResource
```json
{
  "id": "integer",
  "cart": {
    "id": "integer"
  },
  "product": {
    "id": "integer",
    "name": "string",
    "slug": "string"
  },
  "quantity": "integer",
  "price_snapshot": "integer"
}
```

### CartResource
```json
{
  "id": "integer",
  "items": CartItemResource[]
}
```

### ShippingAddressResource
```json
{
  "id": "integer",
  "user_id": "integer",
  "recipient_name": "string",
  "phone": "string",
  "label": "rumah|kantor|kos|apartemen|toko|gudang|lainnya|null",
  "province": "string",
  "city": "string",
  "district": "string",
  "postal_code": "string",
  "address": "string",
  "is_default": "boolean"
}
```

### OrderItemResource
```json
{
  "id": "integer",
  "order_id": "integer",
  "product_id": "integer",
  "product_sku": "string",
  "product_name": "string",
  "price": "integer",
  "quantity": "integer",
  "subtotal": "integer"
}
```

### OrderResource
```json
{
  "id": "integer",
  "order_number": "string",
  "status": "pending_payment|payment_failed|paid|processing|shipped|completed|cancelled",
  "items_count": "integer",
  "items": OrderItemResource[],
  "subtotal": "integer",
  "total": "integer",
  "created_at": "Y-m-d H:i:s"
}
```

### PaymentResource
```json
{
  "id": "integer|uuid",
  "order_id": "integer",
  "gateway": "string",
  "gateway_transaction_id": "string|null",
  "payment_method": "string|null",
  "status": "pending|paid|failed|expired|cancelled|refunded",
  "amount": "integer",
  "payment_url": "string|null",
  "expired_at": "datetime|null",
  "paid_at": "datetime|null",
  "snap_token": "string|null"
}
```

### CheckoutItemResource
```json
{
  "id": "integer",
  "product_id": "integer",
  "product_sku": "string",
  "product_name": "string",
  "quantity": "integer",
  "unit_price": "integer",
  "subtotal": "integer"
}
```

### CheckoutResource
```json
{
  "shipping_address": ShippingAddressResource,
  "summary": {
    "subtotal": "integer",
    "shipping_cost": "integer",
    "total": "integer"
  },
  "items": CheckoutItemResource[]
}
```

---

## 7. ENUMS

### UserRole
```
- admin (full access)
- seller (can manage store and products)
- customer (default role for new users)

Methods:
- canAccess(role): Hierarchical access check
- isAdmin(), isSeller(), isCustomer(): Boolean checks
```

### ProductStatus
```
- draft (not visible to customers)
- published (visible to customers)
```

### OrderStatus
```
- pending_payment (initial state after order creation)
- payment_failed (payment processing failed)
- paid (payment confirmed)
- processing (seller preparing order)
- shipped (order in transit)
- completed (order delivered and closed)
- cancelled (order cancelled)

Allowed Transitions:
  pending_payment → paid, cancelled
  paid → processing, cancelled
  processing → shipped
  shipped → completed
  completed, cancelled → (final states)
```

### PaymentStatus
```
- pending (waiting for payment)
- paid (payment confirmed)
- failed (payment failed)
- expired (payment expired)
- cancelled (payment cancelled)
- refunded (payment refunded)

Allowed Transitions:
  pending → paid, failed, expired, cancelled
  paid → refunded
  failed, expired, cancelled → (no transitions)
```

### PaymentOutcome
```
- pending (payment awaiting processing)
- success (payment successful)
- failed (payment failed)
- expired (payment expired)
- cancelled (payment cancelled)
```

### StoreStatus
```
- active (store is operational)
- suspended (store temporarily suspended)
- pending (store pending approval)
```

### AddressLabel (Indonesian)
```
- rumah (house/home)
- kantor (office)
- kos (boarding house)
- apartemen (apartment)
- toko (shop/store)
- gudang (warehouse)
- lainnya (other/general)
```

### PaymentStatusTransition
```
Tracks allowed payment status transitions
```

### OrderStatusTransition
```
Tracks allowed order status transitions
```

### InventoryHistoryType
```
Tracks inventory change types (in/out/adjustment)
```

---

## 8. SPECIAL FEATURES

### Image Upload & File Handling
- **Location**: `storage/app/public/products/`
- **Max Size**: 3MB per image
- **Formats**: JPG, PNG, GIF, WebP
- **Features**:
  - Automatic sort order management
  - Reordering endpoint to change image order
  - Automatic cleanup on deletion
  - Multiple images per product supported

### Payment Gateway Integration
- **Primary Gateway**: Midtrans
- **Configuration**: `config/payment.php`
- **Environment Variables**:
  - `MIDTRANS_SERVER_KEY`
  - `MIDTRANS_CLIENT_KEY`
  - `MIDTRANS_IS_PRODUCTION` (default: false)
  - `MIDTRANS_IS_SANITIZED` (default: true)
  - `MIDTRANS_IS_3DS` (default: true)
- **Features**:
  - Webhook handling for payment status updates
  - Payment URL generation
  - Snap token for embedded payment
  - Automatic order status updates on payment

### Caching Strategy
- **Product Listings**: 10-minute cache with version-based invalidation
- **Cache Key**: Includes pagination, search, filters, sort parameters
- **Invalidation**: On product create, update, delete, restore

### Email Verification
- **Mechanism**: Signed URLs with hash verification
- **Flow**:
  1. User registers
  2. Verification email sent
  3. User clicks signed link
  4. Email marked as verified
- **Required For**: Cart operations, orders, checkout

### Cart Management
- **Storage**: Database (not session)
- **Features**:
  - Persistent across sessions
  - Price snapshots (captures price at time of adding)
  - Quantity increments on duplicate product
  - Bulk clear operation

### Order Lifecycle
1. **Creation**: Order created from cart (PENDING_PAYMENT)
2. **Payment**: Payment initiated → Payment webhook updates status
3. **Processing**: Once paid, order moves to PROCESSING (seller updates)
4. **Shipping**: Seller marks as SHIPPED
5. **Completion**: Status updates to COMPLETED
6. **Alternative**: Order can be cancelled at any non-final status

### Pagination
- **Default**: 10 items per page
- **Max**: 100 items per page
- **Format**: Standard Laravel pagination with cursor/offset
- **Parameters**: `page`, `per_page`

### Search & Filtering
- **Product Search**: Full-text search by name, description
- **Filters**: 
  - By category
  - By tag
  - By price range (min_price, max_price)
  - By stock availability (in_stock)
- **Sorting**: By name, price, created_at, updated_at (ascending/descending with `-` prefix)

### Soft Deletes
- **Affected Models**: User, Product, Category, Tag, Store
- **Behavior**: Deleted records remain in database with `deleted_at` timestamp
- **Restore**: Admin can restore deleted resources
- **Exclusion**: Queries automatically exclude soft-deleted records unless explicitly included

### Role-Based Access Control
- **Admin**: Full access to all resources
- **Seller**: Can manage own store and products; can place orders
- **Customer**: Can browse products, create orders, manage profile

### Response Format
- **Success**: `{ "data": {...}, "message": "string" }`
- **Pagination**: `{ "data": [...], "meta": { "total": int, "per_page": int, "current_page": int } }`
- **Error**: `{ "message": "string", "errors": {...} }` (status code 4xx/5xx)

---

## 9. ERROR HANDLING

### HTTP Status Codes
- **200 OK**: Successful GET/PATCH request
- **201 Created**: Successful POST request (resource created)
- **400 Bad Request**: Validation error
- **401 Unauthorized**: Missing/invalid authentication
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **409 Conflict**: Business logic conflict (e.g., user already has store)
- **422 Unprocessable Entity**: Validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Server Error**: Internal server error

### Common Error Responses
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## 10. API VERSIONING

- **Current Version**: v1
- **URL Pattern**: `/api/v1/...`
- **Location**: Routes defined in `routes/api/v1/` directory
- **Structure**:
  - `public.php`: Unauthenticated endpoints
  - `protected.php`: Authenticated user endpoints
  - `seller.php`: Seller-specific endpoints

---

## 11. REQUEST/RESPONSE EXAMPLES

### Registration
```bash
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+62812345678"
}

Response (201):
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "created_at": "2024-01-01T12:00:00Z"
  },
  "access_token": "1|abcd1234...",
  "token_type": "Bearer"
}
```

### Product List with Filtering
```bash
GET /api/v1/products?category=electronics&min_price=100000&max_price=5000000&sort=-price&per_page=20
```

### Add to Cart
```bash
POST /api/v1/cart/items/product-slug
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 2
}

Response (201):
{
  "id": 1,
  "cart": { "id": 1 },
  "product": { "id": 1, "name": "Product", "slug": "product" },
  "quantity": 2,
  "price_snapshot": 150000
}
```

### Create Order
```bash
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "shipping_address_id": 1
}

Response (201):
{
  "id": 1,
  "order_number": "ORD-2024-001",
  "status": "pending_payment",
  "items_count": 2,
  "subtotal": 300000,
  "total": 350000,
  "created_at": "2024-01-01 12:00:00"
}
```

---

## 12. AUTHENTICATION HEADERS

All authenticated endpoints require:
```
Authorization: Bearer {access_token}
```

Example:
```bash
curl -H "Authorization: Bearer 1|abcd1234..." https://api.example.com/api/v1/me
```

---

## 13. DATABASE RELATIONSHIPS SUMMARY

```
User
  ├── Store (1:1)
  ├── Cart (1:1)
  ├── ShippingAddress (1:many)
  ├── Order (1:many)
  └── PersonalAccessToken/Sanctum (1:many)

Store
  ├── User (many:1)
  └── Product (1:many)

Product
  ├── Store (many:1)
  ├── Category (many:1)
  ├── Tag (many:many)
  ├── ProductImage (1:many)
  ├── CartItem (1:many)
  └── OrderItem (1:many)

Category
  └── Product (1:many)

Tag
  └── Product (many:many)

ProductImage
  └── Product (many:1)

Cart
  ├── User (many:1)
  └── CartItem (1:many)

CartItem
  ├── Cart (many:1)
  └── Product (many:1)

ShippingAddress
  └── User (many:1)

Order
  ├── User (many:1)
  ├── OrderItem (1:many)
  └── Payment (1:many)

OrderItem
  ├── Order (many:1)
  └── Product (many:1)

Payment
  └── Order (many:1)
```

---

## Summary Statistics

- **Total Endpoints**: ~60+
- **Controllers**: 11
- **Models**: 13
- **Resources**: 12
- **Form Requests**: 20+
- **Enums**: 6+
- **Middleware**: 1 custom + 4 built-in
- **Authentication**: Sanctum (Bearer tokens)
- **Database**: MySQL/PostgreSQL compatible
- **Payment Gateway**: Midtrans

