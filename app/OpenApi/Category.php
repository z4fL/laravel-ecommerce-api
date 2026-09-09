<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Category
{
    #[OA\Get(
        path: '/categories',
        operationId: 'listCategories',
        tags: ['Categories'],
        summary: 'List categories',
        responses: [
            new OA\Response(response: 200, description: 'Categories.', content: new OA\JsonContent(ref: '#/components/schemas/CategoryCollection')),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/categories',
        operationId: 'createCategory',
        tags: ['Categories'],
        summary: 'Create a category',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Electronics'),
                new OA\Property(property: 'description', type: 'string', nullable: true, minLength: 10, maxLength: 1000),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Category created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCategory')),
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
        path: '/categories/{category}',
        operationId: 'getCategory',
        tags: ['Categories'],
        summary: 'Get a category',
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics')],
        responses: [
            new OA\Response(response: 200, description: 'Category.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCategory')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/categories/{category}',
        operationId: 'replaceCategory',
        tags: ['Categories'],
        summary: 'Update a category',
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 50),
            new OA\Property(property: 'description', type: 'string', minLength: 10, maxLength: 1000),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Category updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCategory')),
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
        path: '/categories/{category}',
        operationId: 'updateCategory',
        tags: ['Categories'],
        summary: 'Update a category',
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 50),
            new OA\Property(property: 'description', type: 'string', minLength: 10, maxLength: 1000),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Category updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCategory')),
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
        path: '/categories/{category}',
        operationId: 'deleteCategory',
        tags: ['Categories'],
        summary: 'Delete a category',
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics')],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
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
        path: '/categories/{category}/restore',
        operationId: 'restoreCategory',
        tags: ['Categories'],
        summary: 'Restore a deleted category',
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics')],
        responses: [
            new OA\Response(response: 200, description: 'Category restored.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
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
