<?php

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('reports availability at the stock boundary', function () {
    $product = new Product(['stock' => 5]);
    $service = new InventoryService;

    expect($service->checkAvailability($product, 5))->toBeTrue()
        ->and($service->checkAvailability($product, 6))->toBeFalse();
});

it('rejects non-positive availability quantities', function () {
    expect(fn () => (new InventoryService)->checkAvailability(
        new Product(['stock' => 5]),
        0,
    ))->toThrow(ValidationException::class);
});
