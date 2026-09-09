<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Order
{
    #[OA\Get(
        path: '/orders',
        operationId: 'listMyOrders',
        tags: ['Orders'],
        summary: 'List orders belonging to the authenticated user',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated owned orders.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedOrders')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/orders',
        operationId: 'createOrder',
        tags: ['Orders'],
        summary: 'Create an order from the authenticated user cart',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['shipping_address_id'],
            properties: [new OA\Property(property: 'shipping_address_id', type: 'integer', example: 1)]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Order created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessOrder')),
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

    #[OA\Get(
        path: '/orders/{order}',
        operationId: 'getMyOrder',
        tags: ['Orders'],
        summary: 'Get an owned order',
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'Order.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessOrder')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/orders/{order}/cancel',
        operationId: 'cancelMyOrder',
        tags: ['Orders'],
        summary: 'Cancel an owned cancellable order',
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled or returned idempotently.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessOrder')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function cancel(): void {}
}
