<?php

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/v1/orders/{order}/cancel', function () {
    beforeEach(function () {
        $this->endpoint = '/api/v1/orders';


        $this->customer = User::factory()->customer()->create();

        $this->order = Order::factory()
            ->for($this->customer)
            ->create();

        OrderItem::factory()->count(2)->create([
            'order_id' => $this->order->id,
        ]);
    });

    dataset('uncancellable order statuses', [
        // 'paid' => OrderStatus::PAID,
        'processing' => OrderStatus::PROCESSING,
        'shipped' => OrderStatus::SHIPPED,
        'completed' => OrderStatus::COMPLETED,
        'cancelled' => OrderStatus::CANCELLED,
    ]);

    it('requires authentication', function () {
        // api/v1/orders/{order}/cancel
        $response = $this->postJson("{$this->endpoint}/{$this->order->id}/cancel");
        // dd($response->json());
        $response->assertUnauthorized();
    });

    it('allows the order owner to cancel a pending payment order', function () {
        $this->actingAs($this->customer, 'api');

        $this->postJson("{$this->endpoint}/{$this->order->id}/cancel")
            ->assertOk();

        expect($this->order->fresh()->status)
            ->toBe(OrderStatus::CANCELLED);
    });

    it('returns not found when cancelling another customer order', function () {
        $anotherCustomer = User::factory()->customer()->create();

        $this->actingAs($anotherCustomer, 'api');

        $this->postJson("{$this->endpoint}/{$this->order->id}/cancel")
            ->assertNotFound();
    });

    it('returns validation error when order cannot be cancelled', function (OrderStatus $status) {
        $this->order->update([
            'status' => $status,
        ]);

        $this->actingAs($this->customer, 'api');

        $this->postJson("{$this->endpoint}/{$this->order->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        expect($this->order->fresh()->status)
            ->toBe($status);
    })->with('uncancellable order statuses');

    it('updates the order status to cancelled', function () {
        $this->actingAs($this->customer, 'api');

        $this->postJson("{$this->endpoint}/{$this->order->id}/cancel");

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => OrderStatus::CANCELLED,
        ]);
    });

    it('returns the updated order resource', function () {
        $this->actingAs($this->customer, 'api');

        $response = $this->postJson("{$this->endpoint}/{$this->order->id}/cancel");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->order->id)
            ->assertJsonPath('data.status', OrderStatus::CANCELLED->value)
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonCount(2, 'data.items');
    });
});
