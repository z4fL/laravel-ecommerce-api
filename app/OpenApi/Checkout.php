<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Checkout
{
    #[OA\Post(
        path: '/checkout',
        operationId: 'previewCheckout',
        tags: ['Checkout'],
        summary: 'Preview checkout for the authenticated user cart',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['shipping_address_id'],
            properties: [new OA\Property(property: 'shipping_address_id', type: 'integer', example: 1)]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Checkout preview.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCheckout')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function preview(): void {}
}
