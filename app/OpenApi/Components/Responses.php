<?php

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationError',
    description: 'Validation failed.',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
)]
#[OA\Response(
    response: 'UnauthorizedError',
    description: 'Authentication is required or the credentials are invalid.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'Unauthenticated.',
            'data' => null,
        ]
    )
)]
#[OA\Response(
    response: 'ForbiddenError',
    description: 'The request is forbidden.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'You are not authorized to perform this action.',
            'data' => null,
        ]
    )
)]
#[OA\Response(
    response: 'NotFoundError',
    description: 'The requested resource was not found.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'Resource not found.',
            'data' => null,
        ]
    )
)]
#[OA\Response(
    response: 'ConflictError',
    description: 'The request conflicts with the current state.',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
#[OA\Response(
    response: 'BadRequestError',
    description: 'The request was invalid.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'Unsupported webhook gateway.',
            'data' => null,
        ]
    )
)]
#[OA\Response(
    response: 'TooManyRequestsError',
    description: 'Too many requests.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'Too Many Requests',
            'data' => null,
        ]
    )
)]
#[OA\Response(
    response: 'ServerError',
    description: 'Internal server error.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'success' => false,
            'message' => 'Internal Server Error',
            'data' => null,
        ]
    )
)]
class Responses {}
