<?php

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/v1/orders/{order}/cancel', function () {
    it('requires authentication and ownership', function () {
        $owner = User::factory()->customer()->create();
        $order = Order::factory()->for($owner)->create();

        $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertUnauthorized();
        $this->actingAs(User::factory()->customer()->create(), 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertNotFound();
    });

    it('cancels a pending order and returns the updated resource', function () {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->has(OrderItem::factory()->count(2))->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::CANCELLED->value)
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonCount(2, 'data.items');

        expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
    });

    it('rejects cancellation for a non-cancellable status', function (OrderStatus $status) {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->create(['status' => $status]);

        $this->actingAs($customer, 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        expect($order->fresh()->status)->toBe($status);
    })->with([
        OrderStatus::PROCESSING,
        OrderStatus::SHIPPED,
        OrderStatus::COMPLETED,
    ]);
});