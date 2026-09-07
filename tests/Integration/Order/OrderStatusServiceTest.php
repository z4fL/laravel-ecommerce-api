<?php

use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('order status service', function () {
    it('persists an allowed transition', function (OrderStatus $from, OrderStatus $to) {
        $order = Order::factory()->create(['status' => $from]);

        $transition = app(OrderStatusService::class)->update($order, $to);

        expect($transition)->toBe(OrderStatusTransition::TRANSITIONED);
        expect($order->fresh()->status)->toBe($to);
    })->with([
        [OrderStatus::PENDING_PAYMENT, OrderStatus::PAID],
        [OrderStatus::PAID, OrderStatus::PROCESSING],
        [OrderStatus::PROCESSING, OrderStatus::SHIPPED],
        [OrderStatus::SHIPPED, OrderStatus::COMPLETED],
    ]);

    it('returns idempotent without changing the order', function () {
        $order = Order::factory()->create(['status' => OrderStatus::PAID]);

        $transition = app(OrderStatusService::class)->update($order, OrderStatus::PAID);

        expect($transition)->toBe(OrderStatusTransition::IDEMPOTENT);
        expect($order->fresh()->status)->toBe(OrderStatus::PAID);
    });

    it('returns conflict and preserves the current status for an invalid transition', function () {
        $order = Order::factory()->create(['status' => OrderStatus::COMPLETED]);

        $transition = app(OrderStatusService::class)->update($order, OrderStatus::PAID);

        expect($transition)->toBe(OrderStatusTransition::CONFLICT);
        expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
    });
});