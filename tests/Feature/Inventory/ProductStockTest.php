<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated seller to set stock', function () {
    $seller = User::factory()->seller()->create();
    $store = Store::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['store_id' => $store->id, 'stock' => 4]);

    $this->actingAs($seller, 'api')
        ->patchJson('/api/v1/store/products/'.$product->slug.'/stock', ['stock' => 12])
        ->assertOk()
        ->assertJsonPath('data.stock', 12);

    expect($product->fresh()->stock)->toBe(12);
});

it('rejects invalid stock and products outside the sellers store', function () {
    $seller = User::factory()->seller()->create();
    $store = Store::factory()->create(['user_id' => $seller->id]);
    $otherProduct = Product::factory()->create(['stock' => 4]);

    $this->actingAs($seller, 'api')
        ->patchJson('/api/v1/store/products/'.$otherProduct->slug.'/stock', ['stock' => 12])
        ->assertNotFound();

    $product = Product::factory()->create(['store_id' => $store->id, 'stock' => 4]);

    $this->patchJson('/api/v1/store/products/'.$product->slug.'/stock', ['stock' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['stock']);

    expect($product->fresh()->stock)->toBe(4);
});
