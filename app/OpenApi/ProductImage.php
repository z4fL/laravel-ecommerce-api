<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ProductImage
{
    #[OA\Post(
        path: '/store/products/{store_product}/images',
        operationId: 'uploadStoreProductImage',
        tags: ['Product Images'],
        summary: 'Upload an image for an owned product',
        parameters: [new OA\Parameter(
            name: 'store_product',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'gaming-laptop'
        )],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [new OA\Property(
                        property: 'image',
                        type: 'string',
                        format: 'binary',
                        description: 'Image file validated by Laravel File::image(); maximum size 3072 KB.'
                    )],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Product image uploaded.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessProductImage')),
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

    #[OA\Patch(
        path: '/store/products/{store_product}/images/reorder',
        operationId: 'reorderStoreProductImages',
        tags: ['Product Images'],
        summary: 'Reorder all images for an owned product',
        parameters: [new OA\Parameter(
            name: 'store_product',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'gaming-laptop'
        )],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['image_ids'],
            properties: [new OA\Property(
                property: 'image_ids',
                type: 'array',
                items: new OA\Items(type: 'integer'),
                description: 'Distinct existing image IDs. Every image belonging to the product must be included.',
                example: [3, 1, 2]
            )]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Images reordered.', content: new OA\JsonContent(ref: '#/components/schemas/ProductImageCollection')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function reorder(): void {}

    #[OA\Delete(
        path: '/store/products/{store_product}/images/{image}',
        operationId: 'deleteStoreProductImage',
        tags: ['Product Images'],
        summary: 'Delete an image from an owned product',
        parameters: [
            new OA\Parameter(name: 'store_product', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'gaming-laptop'),
            new OA\Parameter(name: 'image', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product image deleted.', content: new OA\JsonContent(ref: '#/components/schemas/SuccessMessage')),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthorizedError'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenError'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFoundError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequestsError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
        security: [['sanctum' => []]]
    )]
    public function destroy(): void {}
}
