<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class EmailVerification
{
    #[OA\Post(
        path: '/auth/email/verification-notification',
        operationId: 'sendEmailVerificationNotification',
        tags: ['Email Verification'],
        summary: 'Send an email verification link',
        responses: [
            new OA\Response(response: 200, description: 'Verification notification result.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function send(): void {}

    #[OA\Get(
        path: '/email/verify/{id}/{hash}',
        operationId: 'verifyEmail',
        tags: ['Email Verification'],
        summary: 'Verify a user email address',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
            new OA\Parameter(name: 'hash', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'a-sha1-email-hash'),
            new OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'integer'), example: 1735689600),
            new OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'signed-url-signature'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verification result.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: []
    )]
    public function verify(): void {}
}
