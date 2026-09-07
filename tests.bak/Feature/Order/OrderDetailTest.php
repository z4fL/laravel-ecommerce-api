<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('GET /api/v1/orders/{order}', function () {
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

    it('requires authentication', function () {
        $response = $this->getJson("{$this->endpoint}/{$this->order->id}");

        $response->assertUnauthorized();
    });

    it('returns order detail', function () {
        actingAs($this->customer);

        $response = $this->getJson("{$this->endpoint}/{$this->order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->order->id)
            ->assertJsonPath('data.order_number', $this->order->order_number)
            ->assertJsonPath('data.status', $this->order->status->value)
            ->assertJsonPath('data.subtotal', $this->order->subtotal)
            ->assertJsonPath('data.total', $this->order->total);
    });

    it('returns purchased items', function () {
        actingAs($this->customer);

        $response = $this->getJson("{$this->endpoint}/{$this->order->id}");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items');
    });

    it('returns item count', function () {
        actingAs($this->customer);

        $response = $this->getJson("{$this->endpoint}/{$this->order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.items_count', 2);
    });

    it('returns not found when viewing another customer order', function () {
        $anotherCustomer = User::factory()->customer()->create();

        actingAs($anotherCustomer);

        $response = $this->getJson("{$this->endpoint}/{$this->order->id}");

        $response->assertNotFound();
    });

    it('returns not found for non existing order', function () {
        actingAs($this->customer);

        $response = $this->getJson("{$this->endpoint}/999999");

        $response->assertNotFound();
    });
});
