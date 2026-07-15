<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->endpoint = '/api/v1/products';

    // $this->store = Store::factory()->create();
    // $this->categories = Category::factory()->count(2)->create();
});

describe('GET /products', function () {

    it('guest can view published products', function () {
        $products = Product::factory()
            ->count(3)
            ->published()
            ->create();
        // ->create([
        //     'store_id' => $this->store->id,
        //     'category_id' => fn() => $this->categories->random()->id,
        // ]);

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount($products->count(), 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'slug',
                        'description',
                        'price',
                        'status',
                        'store' => [
                            'id',
                            'name',
                        ],
                        'category',
                        'tags',
                    ],
                ],
                'meta',
                'links',
            ]);
    });

    it('authenticated user can view published products', function () {
        $products = Product::factory()
            ->count(2)
            ->published()
            ->create();

        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'api')
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount($products->count(), 'data');
    });

    it('draft products are not included in catalog', function () {
        $publishedProduct =  Product::factory()->count(2)->published()->create();
        Product::factory()->count(1)->draft()->create();

        $response = $this->getJson($this->endpoint);

        $response
            ->assertOk()
            ->assertJsonCount($publishedProduct->count(), 'data');

        collect($response->json('data'))
            ->each(fn($product) => expect($product['status'])->not->toBe('draft'));
    });

    it('search only returns published products', function () {
        Product::factory()->published()->create([
            'name' => 'Gaming Laptop',
        ]);

        Product::factory()->draft()->create([
            'name' => 'Gaming Laptop Beta',
        ]);

        $searchQuery = 'Gaming';
        $response = $this->getJson("{$this->endpoint}?search={$searchQuery}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaming Laptop');
    });

    it('filter only returns published products', function () {
        $category = Category::factory()->create();

        Product::factory()
            ->published()
            ->create([
                'category_id' => $category->id,
            ]);

        Product::factory()
            ->draft()
            ->create([
                'category_id' => $category->id,
            ]);

        $this->getJson("{$this->endpoint}?category={$category->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('sorting still works',  function () {
        Product::factory()->published()->create([
            'name' => 'Zebra',
        ]);

        Product::factory()->published()->create([
            'name' => 'Apple',
        ]);

        $response = $this->getJson("{$this->endpoint}?sort=name");
        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('data.1.name', 'Zebra');
    });

    it('7# pagination still works', function () {
        Product::factory()->count(15)->published()->create();

        $this->getJson("{$this->endpoint}?per_page=10")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
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

describe('GET /products/{public_product}', function () {

    it('guest can view published product', function () {
        $product = Product::factory()->published()->create();

        $this->getJson("{$this->endpoint}/{$product->slug}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.slug', $product->slug);
    });

    it('authenticated user can view published product', function () {
        $product = Product::factory()->published()->create();
        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'api')
            ->getJson("{$this->endpoint}/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug);
    });

    it('draft product returns 404', function () {
        $product = Product::factory()->draft()->create();

        $this->getJson("{$this->endpoint}/{$product->slug}")
            ->assertNotFound();
    });

    it('non existent product returns 404', function () {
        $this->getJson("{$this->endpoint}/product-does-not-exist")
            ->assertNotFound();
    });
});
