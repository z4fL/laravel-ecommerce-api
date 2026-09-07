<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->seller()->create();

    $this->store = Store::factory()->create([
        'user_id' => $this->seller->id,
    ]);

    $this->product = Product::factory()->create([
        'store_id' => $this->store->id,
        'stock' => 10,
    ]);

    $this->endpoint = "/api/v1/store/products/{$this->product->slug}/stock";

    $this->actingAs($this->seller, 'api');
});

describe('PATCH /store/products/{store_product}/stock', function () {

    it('seller can update own product stock', function () {
        $this->patchJson($this->endpoint, [
            'stock' => 25,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.stock', 25);
    });

    it('seller cannot update another seller product stock', function () {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->patchJson(
            "/api/v1/store/products/{$product->slug}/stock",
            [
                'stock' => 50,
            ]
        )->assertNotFound();

        expect($product->fresh()->stock)
            ->toBe(10);
    });

    it('product stock is updated in database', function () {
        $this->patchJson($this->endpoint, [
            'stock' => 99,
        ])->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 99,
        ]);
    });

    it('validation works', function () {
        $this->patchJson($this->endpoint, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'stock',
            ]);
    });

    it('negative stock is rejected', function () {
        $this->patchJson($this->endpoint, [
            'stock' => -1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'stock',
            ]);

        expect($this->product->fresh()->stock)
            ->toBe(10);
    });

    it('product slug route works', function () {
        $this->patchJson(
            "/api/v1/store/products/{$this->product->slug}/stock",
            [
                'stock' => 20,
            ]
        )
            ->assertOk();

        expect($this->product->fresh()->stock)
            ->toBe(20);
    });

});
