<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('POST /api/v1/checkout', function () {

    beforeEach(function () {
        $this->endpoint = '/api/v1/checkout';
        $this->customer = User::factory()->customer()->create();
        $this->seller = User::factory()->seller()->create();

        $this->product = Product::factory()->published()->create([
            'store_id' => Store::factory()->for($this->seller)->create()->id,
            'price' => 100_000,
            'stock' => 10,
        ]);

        $this->cart = Cart::factory()
            ->for($this->customer)
            ->create();

        CartItem::factory()->create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price_snapshot' => 100_000,
        ]);

        $this->address = ShippingAddress::factory()
            ->for($this->customer)
            ->create();
    });

    it('requires authentication', function () {
        $this->postJson($this->endpoint)
            ->assertUnauthorized();
    });

    it('allows customer to preview checkout', function () {
        actingAs($this->customer);

        $response = $this->postJson($this->endpoint, [
            'shipping_address_id' => $this->address->id,
        ]);

        $response->assertOk();
    });

    it('allows seller to preview checkout', function () {
        $otherSeller = User::factory()->seller()->create();
        $otherStore = Store::factory()->for($otherSeller)->create();

        $product = Product::factory()->published()->create([
            'store_id' => $otherStore->id
        ]);

        $cart = Cart::factory()->for($this->seller)->create();

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id
        ]);

        $address = ShippingAddress::factory()
            ->for($this->seller)
            ->create();

        actingAs($this->seller);

        $this->postJson($this->endpoint, [
            'shipping_address_id' => $address->id,
        ])
            ->assertOk();
    });

    it('returns checkout preview', function () {
        actingAs($this->customer);

        $this->postJson($this->endpoint, [
            'shipping_address_id' => $this->address->id,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'shipping_address',
                    'summary',
                    'items',
                ],
            ]);
    });

    it('validates required shipping address', function () {
        actingAs($this->customer);

        $this->postJson($this->endpoint, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'shipping_address_id',
            ]);
    });

    it('validates shipping address must exist', function () {
        actingAs($this->customer);

        $this->postJson($this->endpoint, [
            'shipping_address_id' => 999999,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'shipping_address_id',
            ]);
    });

    it('fails when shipping address does not belong to authenticated user', function () {
        $anotherUser = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($anotherUser)
            ->create();

        actingAs($this->customer);

        $this->postJson($this->endpoint, [
            'shipping_address_id' => $address->id,
        ])
            ->assertNotFound();
    });

    it('fails when cart is empty', function () {
        CartItem::query()->delete();

        actingAs($this->customer);

        $response = $this->postJson($this->endpoint, [
            'shipping_address_id' => $this->address->id,
        ]);

        $response->assertUnprocessable();
    });

    it('fails when cart validation fails', function () {
        $this->product->update([
            'price' => 150_000,
        ]);

        actingAs($this->customer);

        $this->postJson($this->endpoint, [
            'shipping_address_id' => $this->address->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cart not valid');
    });
});
