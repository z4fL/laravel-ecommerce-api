<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->cartEndpoint = '/api/v1/cart';
    $this->actingAs($this->customer, 'api');
});

describe('cart resource', function () {
    it('returns the authenticated users cart and creates it when missing', function () {
        $this->getJson($this->cartEndpoint)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.items');

        expect($this->customer->fresh()->cart)->not->toBeNull();
    });

    it('clears items but keeps the cart record', function () {
        $cart = Cart::factory()->for($this->customer)->withItems(2)->create();

        $this->deleteJson($this->cartEndpoint)->assertOk();

        expect(Cart::find($cart->id))->not->toBeNull()
            ->and($cart->fresh()->cartItems)->toBeEmpty();
    });

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson($this->cartEndpoint)->assertUnauthorized();
        $this->deleteJson($this->cartEndpoint)->assertUnauthorized();
    });
});

describe('cart items', function () {
    it('adds a product and increments an existing product item', function () {
        $product = Product::factory()->published()->create();

        $this->postJson("{$this->cartEndpoint}/items/{$product->slug}", ['quantity' => 2])
            ->assertCreated();
        $this->postJson("{$this->cartEndpoint}/items/{$product->slug}", ['quantity' => 3])
            ->assertCreated();

        expect(CartItem::query()->where('product_id', $product->id)->sole()->quantity)->toBe(5);
    });

    it('rejects invalid quantities and unknown products', function () {
        $product = Product::factory()->published()->create();

        $this->postJson("{$this->cartEndpoint}/items/{$product->slug}", ['quantity' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson("{$this->cartEndpoint}/items/not-a-product", ['quantity' => 1])
            ->assertNotFound();
    });

    it('prevents adding a sellers own product', function () {
        $seller = User::factory()->seller()->create();
        $store = Store::factory()->for($seller)->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->actingAs($seller, 'api')
            ->postJson("{$this->cartEndpoint}/items/{$product->slug}", ['quantity' => 1])
            ->assertForbidden();
    });

    it('allows ownership-aware update and removal, but rejects another users item', function () {
        $ownedItem = Cart::factory()->for($this->customer)->withItems()->create()->cartItems->first();
        $otherItem = Cart::factory()->for(User::factory()->customer())->withItems()->create()->cartItems->first();

        $this->patchJson("{$this->cartEndpoint}/items/{$ownedItem->id}", ['quantity' => 4])
            ->assertOk();
        $this->patchJson("{$this->cartEndpoint}/items/{$otherItem->id}", ['quantity' => 4])
            ->assertForbidden();
        $this->deleteJson("{$this->cartEndpoint}/items/{$ownedItem->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', ['id' => $ownedItem->id]);
    });

    it('allows an administrator to update and remove any cart item', function () {
        $item = Cart::factory()->for(User::factory()->customer())->withItems()->create()->cartItems->first();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api')
            ->patchJson("{$this->cartEndpoint}/items/{$item->id}", ['quantity' => 7])
            ->assertOk();
        $this->deleteJson("{$this->cartEndpoint}/items/{$item->id}")->assertOk();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    });
});