<?php

use App\Enum\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->seller()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->seller->id,
    ]);

    $this->endpoint = '/api/v1/store/products';

    $this->actingAs($this->seller, 'api');
});

describe("GET /store/products", function () {

    it('seller can list own products', function () {
        Product::factory()->count(3)->create([
            'store_id' => $this->store->id,
        ]);

        Product::factory()->count(2)->create();

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('seller can see draft products', function () {
        Product::factory()->published()->create([
            'store_id' => $this->store->id,
        ]);

        Product::factory()->draft()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->getJson($this->endpoint);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['status' => ProductStatus::DRAFT->value]);

        expect(collect($response->json('data'))
            ->pluck('status'))
            ->toHaveCount(2);
    });

    it('seller cannot see another seller products', function () {
        Product::factory()->count(2)->create([
            'store_id' => $this->store->id,
        ]);

        Product::factory()->count(4)->create();

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('search only searches own products', function () {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Gaming Laptop',
        ]);

        Product::factory()->create([
            'name' => 'Gaming Laptop',
        ]);

        $this->getJson($this->endpoint . '?search=Gaming')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaming Laptop');
    });

    it('filter only filters own products', function () {
        $category = Category::factory()->create();

        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->getJson($this->endpoint . '?category=' . $category->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('sorting only sorts own products', function () {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Zebra',
        ]);

        Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Apple',
        ]);

        Product::factory()->create([
            'name' => 'A Product',
        ]);

        $this->getJson($this->endpoint . '?sort=name')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('data.1.name', 'Zebra');
    });

    it('pagination still works', function () {
        Product::factory()
            ->count(15)
            ->create([
                'store_id' => $this->store->id,
            ]);

        $this->getJson($this->endpoint . '?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'count',
                    'from',
                    'to',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                    'path',
                ],
            ]);
    });
});

describe('GET /store/products/{store_product}', function () {

    it('seller can view own product', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->getJson("{$this->endpoint}/{$product->slug}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.slug', $product->slug);
    });

    it('seller cannot view another seller product', function () {
        $product = Product::factory()->create();

        $this->getJson("{$this->endpoint}/{$product->slug}")
            ->assertNotFound();
    });

    it('non existent product returns 404', function () {
        $this->getJson("{$this->endpoint}/product-does-not-exist")
            ->assertNotFound();
    });
});

describe('POST /store/products', function () {

    it('seller can create product', function () {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'category_id' => $category->id,
            'sku' => 'SKU-123456',
            'name' => 'Gaming Laptop',
            'description' => fake()->paragraph(),
            'price' => 15000000,
            'status' => ProductStatus::PUBLISHED,
            'stock' => 10,
            'tag_ids' => $tags->pluck('id')->all(),
        ];

        $this->postJson($this->endpoint, $payload)
            ->assertCreated()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.name', 'Gaming Laptop');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-123456',
            'name' => 'Gaming Laptop',
        ]);
    });

    it('product belongs to authenticated store', function () {
        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'sku' => 'SKU-654321',
            'name' => 'Mechanical Keyboard',
            'price' => 900000,
            'status' => ProductStatus::PUBLISHED,
            'stock' => 5,
        ];

        $this->postJson($this->endpoint, $payload)
            ->assertCreated();

        expect(Product::first()->store_id)
            ->toBe($this->store->id);
    });

    it('product tags are synced', function () {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $payload = [
            'category_id' => $category->id,
            'sku' => 'SKU-777777',
            'name' => 'Mouse',
            'price' => 300000,
            'status' => ProductStatus::PUBLISHED,
            'stock' => 10,
            'tag_ids' => $tags->pluck('id')->all(),
        ];

        $this->postJson($this->endpoint, $payload)
            ->assertCreated();

        $product = Product::first();

        // expect($product->tags()->count())
        //     ->toBe(3);
        expect(
            $product->fresh()->tags()->pluck('id')->sort()->values()->all()
        )->toBe(
            $tags->pluck('id')->sort()->values()->all()
        );
    });

    it('validation works', function () {
        $this->postJson($this->endpoint, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'category_id',
                'sku',
                'name',
                'price',
                'status',
                'stock',
            ]);
    });

    it('customer cannot create product', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'api');

        $this->postJson($this->endpoint, [])
            ->assertForbidden();
    });

    it('guest cannot create product', function () {
        auth()->logout();

        $this->postJson($this->endpoint, [])
            ->assertUnauthorized();
    });
});

describe('PATCH /store/products/{store_product}', function () {

    it('seller can update own product', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Old Name',
        ]);

        $this->patchJson("{$this->endpoint}/{$product->slug}", [
            'name' => 'New Name',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
        ]);
    });

    it('seller can update tags', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $oldTags = Tag::factory()->count(2)->create();
        $product->tags()->sync($oldTags);

        $newTags = Tag::factory()->count(3)->create();

        $this->patchJson("{$this->endpoint}/{$product->slug}", [
            'tag_ids' => $newTags->pluck('id')->all(),
        ])
            ->assertOk();

        expect($product->fresh()->tags()->pluck('id')->sort()->values()->all())
            ->toBe(
                $newTags->pluck('id')->sort()->values()->all()
            );
    });

    it('tags remain unchanged when tag_ids is omitted', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $tags = Tag::factory()->count(2)->create();

        $product->tags()->sync($tags);

        $this->patchJson("{$this->endpoint}/{$product->slug}", [
            'name' => 'Updated Product',
        ])
            ->assertOk();

        expect($product->fresh()->tags()->pluck('id')->sort()->values()->all())
            ->toBe(
                $tags->pluck('id')->sort()->values()->all()
            );
    });

    it('seller cannot update another seller product', function () {
        $product = Product::factory()->create();

        $this->patchJson("{$this->endpoint}/{$product->slug}", [
            'name' => 'Hacked',
        ])
            ->assertNotFound();
    });

    it('validation works', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->patchJson("{$this->endpoint}/{$product->slug}", [
            'price' => -100,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'price',
            ]);
    });
});

describe("DELETE /store/products/{store_product}", function () {

    it('seller can delete own product', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->deleteJson("{$this->endpoint}/{$product->slug}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    });

    it('seller cannot delete another seller product', function () {
        $product = Product::factory()->create();

        $this->deleteJson("{$this->endpoint}/{$product->slug}")
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    });
});
