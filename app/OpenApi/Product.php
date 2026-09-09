<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Product
{
    #[OA\Get(
        path: '/products',
        operationId: 'listPublicProducts',
        tags: ['Products'],
        summary: 'List published products',
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
            new OA\Response(response: 200, description: 'Paginated published products.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProducts')),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/products/{public_product}',
        operationId: 'getPublicProduct',
        tags: ['Products'],
        summary: 'Get a published product',
        parameters: [new OA\Parameter(name: 'public_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop')],
        responses: [
            new OA\Response(response: 200, description: 'Product.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function show(): void {}
}
