<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Payment
{
    #[OA\Post(
        path: '/orders/{order}/payment',
        operationId: 'createPayment',
        tags: ['Payments'],
        summary: 'Create or reuse a pending payment for an order',
        description: 'The current implementation authenticates and verifies the caller but does not enforce order ownership in PaymentController or a payment policy.',
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 201, description: 'Payment created or existing active payment reused.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessPayment')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function store(): void {}
}
