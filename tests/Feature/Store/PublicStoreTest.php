<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->endpoint = '/api/v1/stores';

    $this->store = Store::factory()->active()->create();
    $this->otherStore = Store::factory()->active()->create();

    $this->category = Category::factory()->create();
    $this->tag = Tag::factory()->create();
});

describe('GET /stores', function () {

    it('guest can view stores', function () {
        Store::factory()->count(3)->active()->create();

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'name',
                        'slug',
                        'description',
                        'status',
                    ],
                ],
            ]);
    });

    it('authenticated user can view stores', function () {
        Store::factory()->count(2)->active()->create();

        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'api')
            ->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    });

});

describe('GET /stores/{store}', function () {

    it('guest can view store', function () {
        $this->getJson("{$this->endpoint}/{$this->store->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $this->store->slug);
    });

    it('authenticated user can view store', function () {
        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'api')
            ->getJson("{$this->endpoint}/{$this->store->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $this->store->slug);
    });

    it('non existent store returns 404', function () {
        $this->getJson('{$this->endpoint}/store-not-found')
            ->assertNotFound();
    });

});

describe('GET /stores/{store}/products', function () {

    it('guest can view published products from store', function () {
        Product::factory()
            ->count(3)
            ->published()
            ->create([
                'store_id' => $this->store->id,
            ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('authenticated user can view published products from store', function () {
        Product::factory()
            ->count(2)
            ->published()
            ->create([
                'store_id' => $this->store->id,
            ]);

        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'api')
            ->getJson("{$this->endpoint}/{$this->store->slug}/products")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('draft products are not included', function () {
        Product::factory()->count(2)->published()->create([
            'store_id' => $this->store->id,
        ]);

        Product::factory()->count(3)->draft()->create([
            'store_id' => $this->store->id,
        ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('search only returns published products', function () {
        Product::factory()->published()->create([
            'store_id' => $this->store->id,
            'name' => 'Gaming Laptop',
        ]);

        Product::factory()->draft()->create([
            'store_id' => $this->store->id,
            'name' => 'Gaming Laptop',
        ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products?search=Gaming")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaming Laptop');
    });

    it('filter only returns published products', function () {
        Product::factory()->published()->create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->draft()->create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
        ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products?category={$this->category->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('sorting still works', function () {
        Product::factory()->published()->create([
            'store_id' => $this->store->id,
            'name' => 'Zebra',
        ]);

        Product::factory()->published()->create([
            'store_id' => $this->store->id,
            'name' => 'Apple',
        ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products?sort=name")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('data.1.name', 'Zebra');
    });

    it('pagination still works', function () {
        Product::factory()
            ->count(15)
            ->published()
            ->create([
                'store_id' => $this->store->id,
            ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products?per_page=10")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'meta',
                'links',
            ]);
    });

    it('another store products are not included', function () {
        Product::factory()->count(2)->published()->create([
            'store_id' => $this->store->id,
        ]);

        Product::factory()->count(3)->published()->create([
            'store_id' => $this->otherStore->id,
        ]);

        $this->getJson("{$this->endpoint}/{$this->store->slug}/products")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('empty store returns empty collection', function () {
        $this->getJson("{$this->endpoint}/{$this->store->slug}/products")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    });

});
