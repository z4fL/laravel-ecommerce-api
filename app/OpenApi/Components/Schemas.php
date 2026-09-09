<?php

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'username', 'email', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'username', type: 'string', example: 'john-doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Token',
    required: ['access_token', 'token_type'],
    properties: [
        new OA\Property(property: 'access_token', type: 'string', example: '1|example-token'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ShippingAddress',
    required: ['id', 'user_id', 'recipient_name', 'phone', 'province', 'city', 'district', 'postal_code', 'address', 'is_default'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'recipient_name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'phone', type: 'string', example: '+6285123456789'),
        new OA\Property(property: 'label', type: 'string', nullable: true, enum: ['rumah', 'kantor', 'kos', 'apartemen', 'toko', 'gudang', 'lainnya'], example: 'rumah'),
        new OA\Property(property: 'province', type: 'string', example: 'West Java'),
        new OA\Property(property: 'city', type: 'string', example: 'Bandung'),
        new OA\Property(property: 'district', type: 'string', example: 'Coblong'),
        new OA\Property(property: 'postal_code', type: 'string', example: '40132'),
        new OA\Property(property: 'address', type: 'string', example: 'Jl. Example No. 1'),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Product',
    required: ['id', 'sku', 'name', 'slug', 'description', 'price', 'status', 'stock'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001'),
        new OA\Property(property: 'name', type: 'string', example: 'Example product'),
        new OA\Property(property: 'slug', type: 'string', example: 'example-product'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'integer', minimum: 0, example: 19999),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
        new OA\Property(property: 'stock', type: 'integer', example: 10),
        new OA\Property(property: 'store', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Example Store'),
        ]),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category', nullable: true),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tag')),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductImage')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ProductImage',
    required: ['id', 'product_id', 'path', 'sort_order'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'path', type: 'string', example: 'products/example.jpg'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Category',
    required: ['id', 'name', 'slug', 'description'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Category'),
        new OA\Property(property: 'slug', type: 'string', example: 'category'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Tag',
    required: ['id', 'name', 'slug'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Featured'),
        new OA\Property(property: 'slug', type: 'string', example: 'featured'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Store',
    required: ['name', 'slug', 'description', 'status'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Example Store'),
        new OA\Property(property: 'slug', type: 'string', example: 'example-store'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'A store description.'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'pending'], example: 'active'),
        new OA\Property(property: 'user', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        ]),
        new OA\Property(property: 'products', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CategoryCollection',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'TagCollection',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tag')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessCategory',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Category created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessTag',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Tag created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Tag'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessProduct',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Product created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ProductImageCollection',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductImage')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessProductImage',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Product Image created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/ProductImage'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UserProfile',
    required: ['id', 'name', 'username', 'email', 'email_verified_at', 'phone', 'role', 'created_at', 'updated_at', 'deleted_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'username', type: 'string', example: 'john-doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'phone', type: 'string', example: '085222555111'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller', 'customer'], example: 'customer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CartProduct',
    required: ['id', 'name', 'slug'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Gaming Laptop'),
        new OA\Property(property: 'slug', type: 'string', example: 'gaming-laptop'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CartItem',
    required: ['id', 'quantity', 'price_snapshot'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cart', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
        ]),
        new OA\Property(property: 'product', ref: '#/components/schemas/CartProduct', nullable: true),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'price_snapshot', type: 'integer', example: 100000),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Cart',
    required: ['id', 'items'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessCartItem',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Cart item created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/CartItem'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessCart',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CheckoutItem',
    required: ['id', 'product_id', 'product_sku', 'product_name', 'quantity', 'unit_price', 'subtotal'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_sku', type: 'string', example: 'SKU-001'),
        new OA\Property(property: 'product_name', type: 'string', example: 'Gaming Laptop'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', type: 'integer', example: 100000),
        new OA\Property(property: 'subtotal', type: 'integer', example: 200000),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CheckoutSummary',
    required: ['items_count', 'subtotal'],
    properties: [
        new OA\Property(property: 'items_count', type: 'integer', example: 1),
        new OA\Property(property: 'subtotal', type: 'integer', example: 200000),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CheckoutPreview',
    required: ['shipping_address', 'summary', 'items'],
    properties: [
        new OA\Property(property: 'shipping_address', ref: '#/components/schemas/ShippingAddress'),
        new OA\Property(property: 'summary', ref: '#/components/schemas/CheckoutSummary'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CheckoutItem')),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessProfile',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/UserProfile'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessCheckout',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/CheckoutPreview'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'OrderItem',
    required: ['id', 'order_id', 'product_id', 'product_sku', 'product_name', 'price', 'quantity', 'subtotal'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_sku', type: 'string', example: 'SKU-001'),
        new OA\Property(property: 'product_name', type: 'string', example: 'Gaming Laptop'),
        new OA\Property(property: 'price', type: 'integer', example: 100000),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'subtotal', type: 'integer', example: 200000),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Order',
    required: ['id', 'order_number', 'status', 'subtotal', 'total', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_number', type: 'string', example: 'ORD-20260909-ABC123'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending_payment', 'payment_failed', 'paid', 'processing', 'shipped', 'completed', 'cancelled'], example: 'pending_payment'),
        new OA\Property(property: 'items_count', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
        new OA\Property(property: 'subtotal', type: 'integer', example: 200000),
        new OA\Property(property: 'total', type: 'integer', example: 200000),
        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-09 10:30:00'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessOrder',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Order created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginatedOrders',
    required: ['success', 'message', 'data', 'meta', 'links'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Payment',
    required: ['id', 'order_id', 'gateway', 'gateway_transaction_id', 'payment_method', 'status', 'amount', 'payment_url', 'expired_at', 'paid_at', 'snap_token'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'gateway', type: 'string', example: 'midtrans'),
        new OA\Property(property: 'gateway_transaction_id', type: 'string', nullable: true, example: 'transaction-1'),
        new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'bank_transfer'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'], example: 'pending'),
        new OA\Property(property: 'amount', type: 'integer', example: 200000),
        new OA\Property(property: 'payment_url', type: 'string', format: 'uri', nullable: true, example: 'https://gateway.example/pay'),
        new OA\Property(property: 'expired_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'snap_token', type: 'string', nullable: true, example: 'example_snap_token'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessPayment',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Payment created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Payment'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'WebhookPayload',
    description: 'PaymentWebhookRequest defines no framework validation rules. Midtrans verification requires order_id, status_code, gross_amount, and signature_key; processing also reads transaction_id and transaction_status.',
    properties: [
        new OA\Property(property: 'order_id', type: 'string', example: 'gateway-order-id'),
        new OA\Property(property: 'status_code', type: 'string', example: '200'),
        new OA\Property(property: 'gross_amount', type: 'string', example: '200000'),
        new OA\Property(property: 'signature_key', type: 'string', example: 'example_signature_string'),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'gateway-transaction-id'),
        new OA\Property(property: 'transaction_status', type: 'string', enum: ['pending', 'capture', 'settlement', 'deny', 'failure', 'expire', 'cancel']),
        new OA\Property(property: 'payment_type', type: 'string', nullable: true, example: 'bank_transfer'),
        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'IDR'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'WebhookResult',
    required: ['payment_id', 'outcome', 'transition'],
    properties: [
        new OA\Property(property: 'payment_id', type: 'integer', example: 1),
        new OA\Property(property: 'outcome', type: 'string', enum: ['pending', 'success', 'failed', 'expired', 'cancelled'], example: 'success'),
        new OA\Property(property: 'transition', type: 'string', enum: ['transitioned', 'idempotent', 'conflict'], example: 'transitioned'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessWebhook',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/WebhookResult'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ValidationError',
    required: ['success', 'message', 'data', 'errors'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'An error occurred.'),
        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessMessage',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessShippingAddress',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Shipping Address created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/ShippingAddress'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SuccessStore',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Store created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Store'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginatedProducts',
    required: ['success', 'message', 'data', 'meta', 'links'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['total', 'per_page', 'current_page', 'last_page', 'count', 'from', 'to'],
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 25),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'count', type: 'integer', example: 10),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 10),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    required: ['first', 'last', 'prev', 'next', 'path'],
    properties: [
        new OA\Property(property: 'first', type: 'string', format: 'uri'),
        new OA\Property(property: 'last', type: 'string', format: 'uri'),
        new OA\Property(property: 'prev', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'next', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'path', type: 'string', format: 'uri'),
    ],
    type: 'object'
)]
class Schemas {}
