<?php

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('order cancellation workflow', function () {
    it('restores stock for every item when a cancellable order transitions', function () {
        $firstProduct = Product::factory()->create(['stock' => 10]);
        $secondProduct = Product::factory()->create(['stock' => 20]);
        $order = Order::factory()->create(['status' => OrderStatus::PAID]);

        OrderItem::factory()->fromProduct($firstProduct)->create([
            'order_id' => $order->id,
            'quantity' => 2,
        ]);
        OrderItem::factory()->fromProduct($secondProduct)->create([
            'order_id' => $order->id,
            'quantity' => 5,
        ]);

        app(OrderService::class)->cancel($order);

        expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
        expect($firstProduct->fresh()->stock)->toBe(12);
        expect($secondProduct->fresh()->stock)->toBe(25);
    });

    it('does not restore stock when cancellation is not allowed', function () {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['status' => OrderStatus::COMPLETED]);

        OrderItem::factory()->fromProduct($product)->create([
            'order_id' => $order->id,
            'quantity' => 3,
        ]);

        expect(fn () => app(OrderService::class)->cancel($order))
            ->toThrow(ValidationException::class);

        expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
        expect($product->fresh()->stock)->toBe(10);
    });

    it('does not restore stock twice for an already cancelled order', function () {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['status' => OrderStatus::CANCELLED]);

        OrderItem::factory()->fromProduct($product)->create([
            'order_id' => $order->id,
            'quantity' => 3,
        ]);

        app(OrderService::class)->cancel($order);

        expect($product->fresh()->stock)->toBe(10);
    });
});