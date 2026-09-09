<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Webhook
{
    #[OA\Post(
        path: '/webhook/payment/{gateway}',
        operationId: 'handlePaymentWebhook',
        tags: ['Payment Webhooks'],
        summary: 'Process a payment gateway webhook',
        description: 'This endpoint is public at the HTTP middleware level. Midtrans requests are authenticated by the signature_key body value computed from order_id, status_code, gross_amount, and the configured server key.',
        parameters: [new OA\Parameter(name: 'gateway', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['midtrans']), example: 'midtrans')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/WebhookPayload')),
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed or duplicate event accepted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessWebhook')),
            new OA\Response(response: 400, description: 'Unsupported payment gateway.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Invalid or incomplete gateway signature.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: []
    )]
    public function handle(): void {}
}
