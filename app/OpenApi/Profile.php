<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Profile
{
    #[OA\Get(
        path: '/me',
        operationId: 'getMyProfile',
        tags: ['Profile'],
        summary: 'Get the authenticated user profile',
        responses: [
            new OA\Response(response: 200, description: 'Current user profile.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProfile')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function show(): void {}

    #[OA\Patch(
        path: '/me',
        operationId: 'updateMyProfile',
        tags: ['Profile'],
        summary: 'Update the authenticated user profile',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 50),
            new OA\Property(property: 'username', type: 'string', minLength: 3, maxLength: 50, pattern: '^[A-Za-z0-9_-]+$'),
            new OA\Property(property: 'phone', type: 'string', maxLength: 20),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProfile')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/me',
        operationId: 'deleteMyProfile',
        tags: ['Profile'],
        summary: 'Delete the authenticated user account',
        responses: [
            new OA\Response(response: 200, description: 'User deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}

    #[OA\Post(
        path: '/users/{user}/restore',
        operationId: 'restoreUserProfile',
        tags: ['Profile'],
        summary: 'Restore a deleted user account',
        parameters: [new OA\Parameter(
            name: 'user',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 1
        )],
        responses: [
            new OA\Response(response: 200, description: 'User restored.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
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
