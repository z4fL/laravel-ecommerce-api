<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CartValidationService::class);

    $this->customer = User::factory()->customer()->create();
});

it('validates a valid cart', function () {

    $product = Product::factory()->published()->create([
        'stock' => 10,
        'price' => 10_000,
    ]);

    $cart = Cart::factory()
        ->for($this->customer)
        ->create();

    CartItem::factory()
        ->for($cart)
        ->for($product)
        ->quantity(2)
        ->snapshot(10_000)
        ->create();

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty()
        ->and($result['summary']['items_count'])->toBe(1)
        ->and($result['summary']['subtotal'])->toBe(20_000)
        ->and($result['items'])->toHaveCount(1);
});

it('fails when product is draft', function () {
    $product = Product::factory()->draft()->create([
        'stock' => 10,
        'price' => 10_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()
        ->for($cart)
        ->for($product)
        ->quantity(2)
        ->snapshot(10_000)
        ->create();

    $result = $this->service->validate($cart);
    expect($result['valid'])->toBeFalse()
        ->and($result['errors'][0]['code'])->toBe('PRODUCT_NOT_PUBLISHED')
        ->and($result['items'])->toBeEmpty();
});

it('fails when stock is insufficient', function () {
    $product = Product::factory()->published()->create([
        'stock' => 1,
        'price' => 10_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()
        ->for($cart)
        ->for($product)
        ->quantity(2)
        ->snapshot(10_000)
        ->create();

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'][0]['code'])->toBe('OUT_OF_STOCK');
});

it('fails when product price has changed', function () {
    $product = Product::factory()->published()->create([
        'stock' => 10,
        'price' => 15_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()
        ->for($cart)
        ->for($product)
        ->quantity(2)
        ->snapshot(10_000)
        ->create();

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'][0]['code'])->toBe('PRICE_CHANGED');
});

it('excludes invalid items from validated items', function () {
    $valid = Product::factory()->published()->create([
        'stock' => 10,
        'price' => 10_000,
    ]);

    $draft = Product::factory()->draft()->create([
        'stock' => 10,
        'price' => 10_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()->for($cart)->for($valid)->create([
        'quantity' => 1,
        'price_snapshot' => 10_000,
    ]);

    CartItem::factory()->for($cart)->for($draft)->create([
        'quantity' => 1,
        'price_snapshot' => 10_000,
    ]);

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['items'])->toHaveCount(1)
        ->and($result['summary']['items_count'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1);
});

it('returns multiple validation errors', function () {
    $draft = Product::factory()->draft()->create([
        'stock' => 10,
        'price' => 10_000,
    ]);

    $outOfStock = Product::factory()->published()->create([
        'stock' => 1,
        'price' => 10_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()->for($cart)->for($draft)->create([
        'quantity' => 1,
        'price_snapshot' => 10_000,
    ]);

    CartItem::factory()->for($cart)->for($outOfStock)->create([
        'quantity' => 5,
        'price_snapshot' => 10_000,
    ]);

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toHaveCount(2)
        ->and($result['items'])->toBeEmpty();
});

it('calculates subtotal correctly', function () {
    $product = Product::factory()->published()->create([
        'stock' => 10,
        'price' => 25_000,
    ]);

    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()
        ->for($cart)
        ->for($product)
        ->quantity(4)
        ->snapshot(25_000)
        ->create();

    $result = $this->service->validate($cart);

    expect($result['summary']['subtotal'])
        ->toBe(100_000);
});
