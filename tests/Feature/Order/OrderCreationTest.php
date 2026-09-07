<?php

use App\Enum\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/v1/orders', function () {
    beforeEach(function () {
        $this->customer = User::factory()->customer()->create();
        $this->address = ShippingAddress::factory()->for($this->customer)->create();
        $this->product = Product::factory()->published()->create([
            'price' => 100_000,
            'stock' => 10,
        ]);
        $this->cart = Cart::factory()->for($this->customer)->create();
        $this->cartItem = CartItem::factory()->for($this->cart)->for($this->product)->create([
            'quantity' => 2,
            'price_snapshot' => $this->product->price,
        ]);
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/orders', ['shipping_address_id' => $this->address->id])
            ->assertUnauthorized();
    });

    it('creates an order from the authenticated users cart and clears the cart', function () {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/orders', ['shipping_address_id' => $this->address->id]);

        $response->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::PENDING_PAYMENT->value)
            ->assertJsonPath('data.items_count', 1);

        $order = Order::query()->with('orderItems')->latest('id')->firstOrFail();
        expect($order->user_id)->toBe($this->customer->id);
        expect($order->orderItems->first()->quantity)->toBe(2);
        expect($order->orderItems->first()->price)->toBe($this->product->price);
        expect($this->cart->fresh()->cartItems)->toBeEmpty();
    });

    it('snapshots the shipping address and item values', function () {
        $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/orders', ['shipping_address_id' => $this->address->id])
            ->assertCreated();

        $order = Order::query()->with('orderItems')->latest('id')->firstOrFail();
        expect($order->recipient_name)->toBe($this->address->recipient_name);
        expect($order->address)->toBe($this->address->address);
        expect($order->subtotal)->toBe(200_000);
        expect($order->orderItems->first()->product_name)->toBe($this->product->name);
    });

    it('rejects an empty or invalid cart', function () {
        $this->cart->cartItems()->delete();

        $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/orders', ['shipping_address_id' => $this->address->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        expect(Order::query()->count())->toBe(0);
    });

    it('rejects a shipping address owned by another user', function () {
        $otherAddress = ShippingAddress::factory()->create();

        $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/orders', ['shipping_address_id' => $otherAddress->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address_id');
    });
});