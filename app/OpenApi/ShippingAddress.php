<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ShippingAddress
{
    #[OA\Get(
        path: '/shipping-addresses',
        operationId: 'listShippingAddresses',
        tags: ['Shipping Addresses'],
        summary: 'List the authenticated user shipping addresses',
        responses: [
            new OA\Response(response: 200, description: 'Shipping addresses.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully.'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ShippingAddress')),
                ]
            )),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/shipping-addresses',
        operationId: 'createShippingAddress',
        tags: ['Shipping Addresses'],
        summary: 'Create a shipping address',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['recipient_name', 'phone', 'province', 'city', 'district', 'postal_code', 'address'],
            properties: [
                new OA\Property(property: 'recipient_name', type: 'string', maxLength: 50),
                new OA\Property(property: 'phone', type: 'string', maxLength: 20, pattern: '^[0-9+\s\-()]+$'),
                new OA\Property(property: 'label', type: 'string', nullable: true, enum: ['rumah', 'kantor', 'kos', 'apartemen', 'toko', 'gudang', 'lainnya']),
                new OA\Property(property: 'province', type: 'string', maxLength: 100),
                new OA\Property(property: 'city', type: 'string', maxLength: 100),
                new OA\Property(property: 'district', type: 'string', maxLength: 100),
                new OA\Property(property: 'postal_code', type: 'string', maxLength: 10),
                new OA\Property(property: 'address', type: 'string', maxLength: 255),
                new OA\Property(property: 'is_default', type: 'boolean'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Address created.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessShippingAddress')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/shipping-addresses/{shipping_address}',
        operationId: 'getShippingAddress',
        tags: ['Shipping Addresses'],
        summary: 'Get an owned shipping address',
        parameters: [new OA\Parameter(name: 'shipping_address', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'Shipping address.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessShippingAddress')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function show(): void {}

    #[OA\Patch(
        path: '/shipping-addresses/{shipping_address}',
        operationId: 'updateShippingAddress',
        tags: ['Shipping Addresses'],
        summary: 'Update an owned shipping address',
        parameters: [new OA\Parameter(name: 'shipping_address', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'recipient_name', type: 'string', maxLength: 50),
            new OA\Property(property: 'phone', type: 'string', maxLength: 20, pattern: '^[0-9+\s\-()]+$'),
            new OA\Property(property: 'label', type: 'string', nullable: true, enum: ['rumah', 'kantor', 'kos', 'apartemen', 'toko', 'gudang', 'lainnya']),
            new OA\Property(property: 'province', type: 'string', maxLength: 100),
            new OA\Property(property: 'city', type: 'string', maxLength: 100),
            new OA\Property(property: 'district', type: 'string', maxLength: 100),
            new OA\Property(property: 'postal_code', type: 'string', maxLength: 10),
            new OA\Property(property: 'address', type: 'string', maxLength: 255),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Address updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessShippingAddress')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    #[OA\Put(
        path: '/shipping-addresses/{shipping_address}',
        operationId: 'replaceShippingAddress',
        tags: ['Shipping Addresses'],
        summary: 'Update an owned shipping address',
        parameters: [new OA\Parameter(name: 'shipping_address', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'recipient_name', type: 'string', maxLength: 50),
            new OA\Property(property: 'phone', type: 'string', maxLength: 20, pattern: '^[0-9+\s\-()]+$'),
            new OA\Property(property: 'label', type: 'string', nullable: true, enum: ['rumah', 'kantor', 'kos', 'apartemen', 'toko', 'gudang', 'lainnya']),
            new OA\Property(property: 'province', type: 'string', maxLength: 100),
            new OA\Property(property: 'city', type: 'string', maxLength: 100),
            new OA\Property(property: 'district', type: 'string', maxLength: 100),
            new OA\Property(property: 'postal_code', type: 'string', maxLength: 10),
            new OA\Property(property: 'address', type: 'string', maxLength: 255),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Address updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessShippingAddress')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/shipping-addresses/{shipping_address}',
        operationId: 'deleteShippingAddress',
        tags: ['Shipping Addresses'],
        summary: 'Delete an owned shipping address',
        parameters: [new OA\Parameter(name: 'shipping_address', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'Address deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}

    #[OA\Patch(
        path: '/shipping-addresses/{shipping_address}/default',
        operationId: 'makeShippingAddressDefault',
        tags: ['Shipping Addresses'],
        summary: 'Make an owned shipping address the default',
        parameters: [new OA\Parameter(name: 'shipping_address', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)],
        responses: [
            new OA\Response(response: 200, description: 'Default address changed.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessShippingAddress')),
            new OA\Response(response: 204, description: 'Address was already the default.'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function makeDefault(): void {}
}
