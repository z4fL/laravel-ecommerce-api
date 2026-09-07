<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->service = app(CartValidationService::class);
});

it('returns an empty cart error without validated items', function () {
    $cart = Cart::factory()->for($this->customer)->create();

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toBe([
            ['code' => 'EMPTY_CART', 'message' => 'Cart is empty.'],
        ])
        ->and($result['items'])->toBeEmpty();
});

it('calculates validated item totals and subtotal', function () {
    $product = Product::factory()->published()->create([
        'price' => 25_000,
        'stock' => 10,
    ]);
    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()->for($cart)->for($product)->create([
        'quantity' => 4,
        'price_snapshot' => 25_000,
    ]);

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeTrue()
        ->and($result['summary'])->toBe(['items_count' => 1, 'subtotal' => 100_000])
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0]['subtotal'])->toBe(100_000);
});

it('reports draft, stock, and price errors while retaining valid items', function () {
    $valid = Product::factory()->published()->create(['price' => 10_000, 'stock' => 5]);
    $draft = Product::factory()->draft()->create(['price' => 10_000, 'stock' => 5]);
    $outOfStock = Product::factory()->published()->create(['price' => 10_000, 'stock' => 1]);
    $priceChanged = Product::factory()->published()->create(['price' => 15_000, 'stock' => 5]);
    $cart = Cart::factory()->for($this->customer)->create();

    CartItem::factory()->for($cart)->for($valid)->create(['quantity' => 1, 'price_snapshot' => 10_000]);
    CartItem::factory()->for($cart)->for($draft)->create(['quantity' => 1, 'price_snapshot' => 10_000]);
    CartItem::factory()->for($cart)->for($outOfStock)->create(['quantity' => 2, 'price_snapshot' => 10_000]);
    CartItem::factory()->for($cart)->for($priceChanged)->create(['quantity' => 1, 'price_snapshot' => 10_000]);

    $result = $this->service->validate($cart);

    expect($result['valid'])->toBeFalse()
        ->and($result['items'])->toHaveCount(1)
        ->and(collect($result['errors'])->pluck('code')->all())
        ->toBe(['PRODUCT_NOT_PUBLISHED', 'OUT_OF_STOCK', 'PRICE_CHANGED']);
});