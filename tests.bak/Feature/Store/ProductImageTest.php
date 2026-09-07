<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->seller = User::factory()->seller()->create();

    $this->store = Store::factory()->create([
        'user_id' => $this->seller->id,
    ]);

    $this->product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $this->endpoint = "/api/v1/store/products/{$this->product->slug}/images";

    $this->actingAs($this->seller, 'api');
});

describe('POST /store/products/{store_product}/images', function () {

    it('seller can upload image to own product', function () {
        $file = UploadedFile::fake()->image('product.jpg');

        $this->postJson($this->endpoint, [
            'image' => $file,
        ])
            ->assertCreated()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'product_id',
                    'path',
                    'sort_order',
                ],
            ]);

        Storage::disk('public')->assertExists(
            ProductImage::first()->path
        );
    });

    it('seller cannot upload image to another seller product', function () {
        $product = Product::factory()->create();

        $endpoint = "/api/v1/store/products/{$product->slug}/images";

        $this->postJson($endpoint, [
            'image' => UploadedFile::fake()->image('product.jpg'),
        ])
            ->assertNotFound();

        expect(ProductImage::count())->toBe(0);
    });

    it('uploaded image is associated with product', function () {
        $this->postJson($this->endpoint, [
            'image' => UploadedFile::fake()->image('image.jpg'),
        ])->assertCreated();

        expect(ProductImage::first()->product_id)
            ->toBe($this->product->id);
    });

    it('uploaded image receives next available sort order', function () {
        ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 1,
        ]);

        ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 2,
        ]);

        ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 3,
        ]);

        $this->postJson($this->endpoint, [
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertCreated();

        expect(
            ProductImage::latest('id')->first()->sort_order
        )->toBe(4);
    });

    it('validation works', function () {
        $this->postJson($this->endpoint, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image',
            ]);
    });

});

describe('PATCH /store/products/{store_product}/images/reorder', function () {

    it('seller can reorder own product images', function () {
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 1,
        ]);

        $image2 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 2,
        ]);

        $image3 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'sort_order' => 3,
        ]);

        $this->patchJson(
            "{$this->endpoint}/reorder",
            [
                'image_ids' => [
                    $image3->id,
                    $image1->id,
                    $image2->id,
                ],
            ]
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect($image1->fresh()->sort_order)->toBe(2);
        expect($image2->fresh()->sort_order)->toBe(3);
        expect($image3->fresh()->sort_order)->toBe(1);
    });

    it('seller cannot reorder another seller product images', function () {
        $product = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->patchJson(
            "/api/v1/store/products/{$product->slug}/images/reorder",
            [
                'image_ids' => [
                    $image->id,
                ],
            ]
        )->assertNotFound();
    });

    it('image sort order is updated correctly', function () {
        $images = ProductImage::factory()
            ->count(5)
            ->sequence(
                ['product_id' => $this->product->id, 'sort_order' => 1],
                ['product_id' => $this->product->id, 'sort_order' => 2],
                ['product_id' => $this->product->id, 'sort_order' => 3],
                ['product_id' => $this->product->id, 'sort_order' => 4],
                ['product_id' => $this->product->id, 'sort_order' => 5],
            )
            ->create();

        $orderedIds = $images
            ->pluck('id')
            ->reverse()
            ->values();

        $this->patchJson(
            "{$this->endpoint}/reorder",
            [
                'image_ids' => $orderedIds,
            ]
        )->assertOk();

        foreach ($orderedIds as $index => $id) {
            expect(
                ProductImage::find($id)->sort_order
            )->toBe($index + 1);
        }
    });

    it('validation works when image ids are missing', function () {
        $this->patchJson(
            "{$this->endpoint}/reorder",
            []
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_ids',
            ]);
    });

    it('validation fails when images do not belong to product', function () {
        $otherProduct = Product::factory()->create();

        $foreignImage = ProductImage::factory()->create([
            'product_id' => $otherProduct->id,
        ]);

        ProductImage::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $this->patchJson(
            "{$this->endpoint}/reorder",
            [
                'image_ids' => [
                    $foreignImage->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_ids',
            ]);
    });

    it('validation fails when not all product images are included', function () {
        $images = ProductImage::factory()
            ->count(3)
            ->create([
                'product_id' => $this->product->id,
            ]);

        $this->patchJson(
            "{$this->endpoint}/reorder",
            [
                'image_ids' => [
                    $images[0]->id,
                    $images[1]->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_ids',
            ]);
    });

});

describe('DELETE /store/products/{store_product}/images/{image}', function () {

    it('seller can delete own product image', function () {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'path' => 'products/test-image.jpg',
        ]);

        Storage::disk('public')->put(
            'products/test-image.jpg',
            'dummy image'
        );

        $this->deleteJson(
            "{$this->endpoint}/{$image->id}"
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('product_images', [
            'id' => $image->id,
        ]);

        Storage::disk('public')
            ->assertMissing('products/test-image.jpg');
    });

    it('seller cannot delete another seller product image', function () {
        $otherProduct = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $otherProduct->id,
        ]);

        $this->deleteJson(
            "/api/v1/store/products/{$otherProduct->slug}/images/{$image->id}"
        )
            ->assertNotFound();

        $this->assertDatabaseHas('product_images', [
            'id' => $image->id,
        ]);
    });

    it('image is removed from database', function () {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $this->deleteJson(
            "{$this->endpoint}/{$image->id}"
        )->assertOk();

        $this->assertDatabaseMissing('product_images', [
            'id' => $image->id,
        ]);
    });

    it('storage file is deleted', function () {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'path' => 'products/delete-me.jpg',
        ]);

        Storage::disk('public')->put(
            'products/delete-me.jpg',
            'dummy'
        );

        $this->deleteJson(
            "{$this->endpoint}/{$image->id}"
        )->assertOk();

        Storage::disk('public')
            ->assertMissing('products/delete-me.jpg');
    });

    it('cannot delete image that does not belong to product', function () {
        $otherProduct = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $otherProduct->id,
        ]);

        $this->deleteJson(
            "{$this->endpoint}/{$image->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('product_images', [
            'id' => $image->id,
        ]);
    });

});
