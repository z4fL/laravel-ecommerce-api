<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class Cart
{
    #[OA\Get(
        path: '/cart',
        operationId: 'getMyCart',
        tags: ['Cart'],
        summary: 'Get the authenticated user cart',
        responses: [
            new OA\Response(response: 200, description: 'Current cart.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCart')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function show(): void {}

    #[OA\Delete(
        path: '/cart',
        operationId: 'clearMyCart',
        tags: ['Cart'],
        summary: 'Remove all items from the authenticated user cart',
        responses: [
            new OA\Response(response: 200, description: 'Cart cleared.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}

    #[OA\Post(
        path: '/cart/items/{public_product}',
        operationId: 'addCartItem',
        tags: ['Cart'],
        summary: 'Add a published product to the authenticated user cart',
        parameters: [new OA\Parameter(
            name: 'public_product',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'gaming-laptop'
        )],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['quantity'],
            properties: [new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 2)]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Cart item created or quantity incremented.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCartItem')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function storeItem(): void {}

    #[OA\Patch(
        path: '/cart/items/{item}',
        operationId: 'updateCartItem',
        tags: ['Cart'],
        summary: 'Update an owned cart item quantity',
        parameters: [new OA\Parameter(
            name: 'item',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 1
        )],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['quantity'],
            properties: [new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 4)]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Cart item updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessCartItem')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function updateItem(): void {}

    #[OA\Delete(
        path: '/cart/items/{item}',
        operationId: 'deleteCartItem',
        tags: ['Cart'],
        summary: 'Delete an owned cart item',
        parameters: [new OA\Parameter(
            name: 'item',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 1
        )],
        responses: [
            new OA\Response(response: 200, description: 'Cart item deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function deleteItem(): void {}
}
