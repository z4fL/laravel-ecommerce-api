<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('POST /api/v1/orders', function () {

    beforeEach(function () {
        $this->customer = User::factory()->customer()->create();
        $this->seller = User::factory()->seller()->create();

        $this->shippingAddress = ShippingAddress::factory()
            ->for($this->customer)
            ->create();
        $this->shippingAddressSeller = ShippingAddress::factory()
            ->for($this->seller)
            ->create();

        $this->product = Product::factory()->published()->create([
            'price' => 100_000,
            'stock' => 12,
        ]);

        $this->cart = Cart::factory()
            ->for($this->customer)
            ->create();
        $this->cartSeller = Cart::factory()
            ->for($this->seller)
            ->create();

        $this->cartItem = CartItem::factory()
            ->for($this->cart)
            ->for($this->product)
            ->create([
                'quantity' => 2,
                'price_snapshot' => $this->product->price
            ]);

        $this->cartItemSeller = CartItem::factory()
            ->for($this->cartSeller)
            ->for($this->product)
            ->create([
                'quantity' => 4,
                'price_snapshot' => $this->product->price
            ]);

        $this->payload = [
            'shipping_address_id' => $this->shippingAddress->id,
        ];
    });

    function createOrder(User $user, array $payload)
    {
        actingAs($user, 'api');

        return test()->postJson('/api/v1/orders', $payload);
    }

    it('allows customer to create an order', function () {
        createOrder($this->customer, $this->payload)
            ->assertCreated();
    });

    it('allows seller to create an order', function () {
        createOrder($this->seller, [
            'shipping_address_id' => $this->shippingAddressSeller->id,
        ])
            ->assertCreated();
    });

    it('fails when cart is empty', function () {
        $this->cart->cartItems()->delete();

        createOrder($this->customer, $this->payload)
            ->assertUnprocessable();
    });

    it('fails when cart validation fails', function () {
        $this->product->update([
            'stock' => 1
        ]);

        createOrder($this->customer, $this->payload)
            ->assertUnprocessable();
    });
    it('fails when shipping address does not belong to authenticated user', function () {
        $anotherUser = User::factory()->customer()->create();
        $address = ShippingAddress::factory()->for($anotherUser)->create();

        createOrder($anotherUser, [
            'shipping_address_id' => $address->id,
        ])
            ->assertNotFound();
    });

    it('creates order items', function () {
        $secondProduct = Product::factory()->published()->create([
            'sku' => 'SKU-SECOND',
            'name' => 'Second Product',
            'price' => 250_000,
            'stock' => 10,
        ]);

        CartItem::factory()
            ->for($this->cart)
            ->for($secondProduct)
            ->create([
                'quantity' => 2,
                'price_snapshot' => $secondProduct->price,
            ]);

        createOrder($this->customer, $this->payload)
            ->assertCreated();

        $order = Order::query()
            ->with('orderItems')
            ->latest('id')
            ->first();

        expect($order)->not->toBeNull();

        expect($order->orderItems)
            ->toHaveCount(2);

        $firstItem = $order->orderItems
            ->firstWhere('product_id', $this->product->id);

        $secondItem = $order->orderItems
            ->firstWhere('product_id', $secondProduct->id);

        expect($firstItem)->not->toBeNull();
        expect($secondItem)->not->toBeNull();

        expect($firstItem->product_sku)->toBe($this->product->sku);
        expect($firstItem->product_name)->toBe($this->product->name);
        expect($firstItem->price)->toBe($this->product->price);
        expect($firstItem->quantity)->toBe($this->cartItem->quantity);
        expect($firstItem->subtotal)
            ->toBe($this->product->price * $this->cartItem->quantity);

        expect($secondItem->product_sku)->toBe($secondProduct->sku);
        expect($secondItem->product_name)->toBe($secondProduct->name);
        expect($secondItem->price)->toBe($secondProduct->price);
        expect($secondItem->quantity)->toBe(2);
        expect($secondItem->subtotal)
            ->toBe($secondProduct->price * 2);
    });

    it('creates shipping address snapshot', function () {
        createOrder($this->customer, [
            'shipping_address_id' => $this->shippingAddress->id,
        ])->assertCreated();

        $order = Order::query()->latest('id')->first();

        expect($order)->not->toBeNull();

        expect($order->recipient_name)
            ->toBe($this->shippingAddress->recipient_name);

        expect($order->phone)
            ->toBe($this->shippingAddress->phone);

        expect($order->province)
            ->toBe($this->shippingAddress->province);

        expect($order->city)
            ->toBe($this->shippingAddress->city);

        expect($order->district)
            ->toBe($this->shippingAddress->district);

        expect($order->postal_code)
            ->toBe($this->shippingAddress->postal_code);

        expect($order->address)
            ->toBe($this->shippingAddress->address);
    });

    it('clears cart after order is created', function () {
        actingAs($this->customer);

        expect($this->cart->cartItems)
            ->toHaveCount(1);

        createOrder($this->customer, [
            'shipping_address_id' => $this->shippingAddress->id,
        ])->assertCreated();

        expect($this->cart->fresh()->cartItems)
            ->toHaveCount(0);
    });
});
