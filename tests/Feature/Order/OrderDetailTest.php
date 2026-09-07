<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /api/v1/orders/{order}', function () {
    it('requires authentication and ownership', function () {
        $owner = User::factory()->customer()->create();
        $order = Order::factory()->for($owner)->create();

        $this->getJson("/api/v1/orders/{$order->id}")->assertUnauthorized();

        $this->actingAs(User::factory()->customer()->create(), 'api')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertNotFound();
    });

    it('returns the order and its items', function () {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->has(OrderItem::factory()->count(2))->create();

        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonCount(2, 'data.items');
    });

    it('returns not found for an unknown order', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'api')->getJson('/api/v1/orders/999999')
            ->assertNotFound();
    });
});