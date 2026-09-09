<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ProductStock
{
    #[OA\Patch(
        path: '/store/products/{store_product}/stock',
        operationId: 'updateStoreProductStock',
        tags: ['Product Stock'],
        summary: 'Set stock for an owned product',
        parameters: [new OA\Parameter(
            name: 'store_product',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'gaming-laptop'
        )],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['stock'],
            properties: [new OA\Property(
                property: 'stock',
                type: 'integer',
                minimum: 0,
                example: 12
            )]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Product stock updated.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProduct')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function update(): void {}
}
