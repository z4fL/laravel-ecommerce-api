<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Auth
{
    #[OA\Post(
        path: '/auth/register',
        operationId: 'register',
        tags: ['Authentication'],
        summary: 'Register a customer',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'username', 'email', 'password', 'password_confirmation', 'phone'],
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'John Doe'),
                new OA\Property(property: 'username', type: 'string', minLength: 3, maxLength: 50, pattern: '^[A-Za-z0-9_-]+$', example: 'john-doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '085222555111'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'User registered.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'User created successfully.'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'access_token', type: 'string', example: '1|example-token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    ]),
                ]
            )),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/auth/login',
        operationId: 'login',
        tags: ['Authentication'],
        summary: 'Authenticate a user',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Login successful.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Login successful.'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'access_token', type: 'string', example: '1|example-token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    ]),
                ]
            )),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'logout',
        tags: ['Authentication'],
        summary: 'Revoke the current access token',
        responses: [
            new OA\Response(response: 200, description: 'Token revoked.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function logout(): void {}

    #[OA\Post(
        path: '/auth/forgot-password',
        operationId: 'forgotPassword',
        tags: ['Authentication'],
        summary: 'Send a password reset link',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email'],
            properties: [new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com')]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Reset link sent.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function forgotPassword(): void {}

    #[OA\Post(
        path: '/auth/reset-password',
        operationId: 'resetPassword',
        tags: ['Authentication'],
        summary: 'Reset a password',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['token', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Password reset.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function resetPassword(): void {}
}
