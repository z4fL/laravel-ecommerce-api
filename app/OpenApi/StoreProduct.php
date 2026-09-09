<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class StoreProduct
{
    #[OA\Get(
        path: '/store/products',
        operationId: 'listMyStoreProducts',
        tags: ['Store Products'],
        summary: 'List products belonging to the authenticated store',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'tag', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number', minimum: 0)),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number', minimum: 0)),
            new OA\Parameter(name: 'in_stock', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['name', '-name', 'price', '-price', 'created_at', '-created_at', 'updated_at', '-updated_at'])),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated store products.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProducts')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/store/products',
        operationId: 'createStoreProduct',
        tags: ['Store Products'],
        summary: 'Create a product in the authenticated store',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['category_id', 'sku', 'name', 'price', 'status', 'stock'],
            properties: [
                new OA\Property(property: 'category_id', type: 'integer', example: 1),
                new OA\Property(property: 'sku', type: 'string', maxLength: 50, example: 'SKU-001'),
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 50),
                new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
                new OA\Property(property: 'price', type: 'integer', minimum: 0, example: 1500000),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
                new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 10),
                new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Product created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/store/products/{store_product}',
        operationId: 'getStoreProduct',
        tags: ['Store Products'],
        summary: 'Get a product belonging to the authenticated store',
        parameters: [new OA\Parameter(name: 'store_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        responses: [
            new OA\Response(response: 200, description: 'Product.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/store/products/{store_product}',
        operationId: 'replaceStoreProduct',
        tags: ['Store Products'],
        summary: 'Update a product belonging to the authenticated store',
        parameters: [new OA\Parameter(name: 'store_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'category_id', type: 'integer'),
            new OA\Property(property: 'sku', type: 'string', maxLength: 50),
            new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 150),
            new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
            new OA\Property(property: 'price', type: 'integer', minimum: 0),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
            new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Product updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    #[OA\Patch(
        path: '/store/products/{store_product}',
        operationId: 'updateStoreProduct',
        tags: ['Store Products'],
        summary: 'Update a product belonging to the authenticated store',
        parameters: [new OA\Parameter(name: 'store_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'category_id', type: 'integer'),
            new OA\Property(property: 'sku', type: 'string', maxLength: 50),
            new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 150),
            new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
            new OA\Property(property: 'price', type: 'integer', minimum: 0),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
            new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Product updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/store/products/{store_product}',
        operationId: 'deleteStoreProduct',
        tags: ['Store Products'],
        summary: 'Delete a product belonging to the authenticated store',
        parameters: [new OA\Parameter(name: 'store_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        responses: [
            new OA\Response(response: 200, description: 'Product deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}

    #[OA\Post(
        path: '/store/products/{restore_product}/restore',
        operationId: 'restoreStoreProduct',
        tags: ['Store Products'],
        summary: 'Restore a deleted product belonging to the authenticated store',
        parameters: [new OA\Parameter(name: 'restore_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        responses: [
            new OA\Response(response: 200, description: 'Product restored.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function restore(): void {}
}
