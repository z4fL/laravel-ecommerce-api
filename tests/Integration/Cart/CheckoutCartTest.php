<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\Store;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates order items from the validated cart and clears the cart', function () {
    $customer = User::factory()->customer()->create();
    $seller = User::factory()->seller()->create();
    $product = Product::factory()->published()->create([
        'store_id' => Store::factory()->for($seller)->create()->id,
        'price' => 100_000,
        'stock' => 10,
    ]);
    $cart = Cart::factory()->for($customer)->create();
    CartItem::factory()->for($cart)->for($product)->create([
        'quantity' => 2,
        'price_snapshot' => 100_000,
    ]);
    $address = ShippingAddress::factory()->for($customer)->create();

    $order = app(OrderService::class)->create($customer, $address);

    expect($order->orderItems)->toHaveCount(1)
        ->and($order->orderItems->first()->quantity)->toBe(2)
        ->and($order->subtotal)->toBe(200_000)
        ->and($cart->fresh()->cartItems)->toBeEmpty();
});

it('does not create an order when cart validation fails', function () {
    $customer = User::factory()->customer()->create();
    $address = ShippingAddress::factory()->for($customer)->create();
    $cart = Cart::factory()->for($customer)->create();
    $product = Product::factory()->published()->create(['price' => 20_000]);
    CartItem::factory()->for($cart)->for($product)->create(['price_snapshot' => 10_000]);

    expect(fn () => app(OrderService::class)->create($customer, $address))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('orders', 0);
    expect($cart->fresh()->cartItems)->toHaveCount(1);
});