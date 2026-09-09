<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Tag
{
    #[OA\Get(
        path: '/tags',
        operationId: 'listTags',
        tags: ['Tags'],
        summary: 'List tags',
        responses: [
            new OA\Response(response: 200, description: 'Tags.', content: new OA\JsonContent(ref: '#/components/schemas/TagCollection')),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/tags',
        operationId: 'createTag',
        tags: ['Tags'],
        summary: 'Create a tag',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Featured')]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Tag created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessTag')),
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
        path: '/tags/{tag}',
        operationId: 'getTag',
        tags: ['Tags'],
        summary: 'Get a tag',
        parameters: [new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'featured')],
        responses: [
            new OA\Response(response: 200, description: 'Tag.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessTag')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/tags/{tag}',
        operationId: 'replaceTag',
        tags: ['Tags'],
        summary: 'Update a tag',
        parameters: [new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'featured')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', maxLength: 50)])),
        responses: [
            new OA\Response(response: 200, description: 'Tag updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessTag')),
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
        path: '/tags/{tag}',
        operationId: 'updateTag',
        tags: ['Tags'],
        summary: 'Update a tag',
        parameters: [new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'featured')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', maxLength: 50)])),
        responses: [
            new OA\Response(response: 200, description: 'Tag updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessTag')),
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
        path: '/tags/{tag}',
        operationId: 'deleteTag',
        tags: ['Tags'],
        summary: 'Delete a tag',
        parameters: [new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'featured')],
        responses: [
            new OA\Response(response: 200, description: 'Tag deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
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
        path: '/tags/{tag}/restore',
        operationId: 'restoreTag',
        tags: ['Tags'],
        summary: 'Restore a deleted tag',
        parameters: [new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'featured')],
        responses: [
            new OA\Response(response: 200, description: 'Tag restored.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
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
