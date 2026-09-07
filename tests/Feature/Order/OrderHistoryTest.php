<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /api/v1/orders', function () {
    it('requires authentication', function () {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    });

    it('returns only the authenticated users newest orders with pagination', function () {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $oldest = Order::factory()->for($customer)->create(['created_at' => now()->subDay()]);
        $newest = Order::factory()->for($customer)->create(['created_at' => now()]);
        Order::factory()->for($otherCustomer)->create();

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/v1/orders?per_page=1');

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'links', 'meta'])
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('meta.per_page', 1);

        expect($response->json('data'))->toHaveCount(1);
        expect($oldest->id)->not->toBe($newest->id);
    });

    it('includes item counts and returns an empty collection without orders', function () {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->has(OrderItem::factory()->count(2))->create();

        $response = $this->actingAs($customer, 'api')->getJson('/api/v1/orders');

        $response->assertOk()->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.items_count', 2);

        $emptyCustomer = User::factory()->customer()->create();
        $this->actingAs($emptyCustomer, 'api')->getJson('/api/v1/orders')
            ->assertOk()->assertJsonCount(0, 'data');
    });

    it('validates pagination parameters', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'api')->getJson('/api/v1/orders?per_page=0')
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
    });
});