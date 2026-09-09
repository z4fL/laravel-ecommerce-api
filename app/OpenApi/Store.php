<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Store
{
    #[OA\Get(
        path: '/stores',
        operationId: 'listStores',
        tags: ['Stores'],
        summary: 'List public stores',
        responses: [
            new OA\Response(response: 200, description: 'Stores.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Store')),
                ]
            )),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/stores/{store}',
        operationId: 'getStore',
        tags: ['Stores'],
        summary: 'Get a public store',
        parameters: [new OA\Parameter(name: 'store', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'example-store')],
        responses: [
            new OA\Response(response: 200, description: 'Store.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessStore')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function show(): void {}

    #[OA\Get(
        path: '/stores/{store}/products',
        operationId: 'listStoreProducts',
        tags: ['Stores'],
        summary: 'List published products for a public store',
        parameters: [
            new OA\Parameter(name: 'store', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'example-store'),
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
            new OA\Response(response: 200, description: 'Paginated published products.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProducts')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function showProducts(): void {}

    #[OA\Post(
        path: '/store',
        operationId: 'createStore',
        tags: ['Seller Store'],
        summary: 'Create the authenticated user store',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'phone', 'status'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 50),
                new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
                new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'pending']),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Store created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessStore')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 409, ref: '#/components/responses/ConflictError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/store',
        operationId: 'getMyStore',
        tags: ['Seller Store'],
        summary: 'Get the authenticated seller store',
        responses: [
            new OA\Response(response: 200, description: 'Seller store.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessStore')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function me(): void {}

    #[OA\Patch(
        path: '/store',
        operationId: 'updateMyStore',
        tags: ['Seller Store'],
        summary: 'Update the authenticated seller store',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 50),
            new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
            new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 20),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Store updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessStore')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/store',
        operationId: 'deleteMyStore',
        tags: ['Seller Store'],
        summary: 'Delete the authenticated seller store',
        responses: [
            new OA\Response(response: 200, description: 'Store deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}
}
