<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('GET /api/v1/orders', function () {
    beforeEach(function () {
        $this->endpoint = '/api/v1/orders';

        $this->customer = User::factory()->customer()->create();
    });

    it('requires authentication', function () {
        $this->getJson($this->endpoint)
            ->assertUnauthorized();
    });

    it('returns paginated order history', function () {
        Order::factory()
            ->count(15)
            ->for($this->customer)
            ->create();

        actingAs($this->customer);

        $response = $this->getJson($this->endpoint);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'links',
                'meta',
            ]);

        expect($response->json('data'))->toHaveCount(10);
    });

    it('returns only authenticated user orders', function () {
        $otherCustomer = User::factory()->customer()->create();

        Order::factory()
            ->count(5)
            ->for($this->customer)
            ->create();

        Order::factory()
            ->count(3)
            ->for($otherCustomer)
            ->create();

        actingAs($this->customer);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();

        expect($response->json('data'))->toHaveCount(5);

        foreach ($response->json('data') as $order) {
            expect(
                Order::find($order['id'])->user_id
            )->toBe($this->customer->id);
        }
    });

    it('returns orders sorted by newest first', function () {
        $oldest = Order::factory()
            ->for($this->customer)
            ->create([
                'created_at' => now()->subDays(2),
            ]);

        $newest = Order::factory()
            ->for($this->customer)
            ->create([
                'created_at' => now(),
            ]);

        actingAs($this->customer);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();

        expect($response->json('data.0.id'))
            ->toBe($newest->id);

        expect($response->json('data.1.id'))
            ->toBe($oldest->id);
    });

    it('returns empty pagination when user has no orders', function () {
        actingAs($this->customer);

        $response = $this->getJson($this->endpoint);

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('supports custom per_page parameter', function () {
        Order::factory()
            ->count(15)
            ->for($this->customer)
            ->create();

        actingAs($this->customer);

        $response = $this->getJson(
            "{$this->endpoint}?per_page=5"
        );

        $response->assertOk();

        expect($response->json('data'))
            ->toHaveCount(5);
    });

    it('validates pagination query parameters', function () {
        actingAs($this->customer);

        $this->getJson("{$this->endpoint}?page=0")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('page');

        $this->getJson("{$this->endpoint}?per_page=0")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->getJson("{$this->endpoint}?per_page=101")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    });

    it('includes order summary information', function () {
        Order::factory()
            ->for($this->customer)
            ->has(OrderItem::factory()->count(2))
            ->create();

        actingAs($this->customer);

        $response = $this->getJson($this->endpoint);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'items_count',
                        'subtotal',
                        'total',
                        'created_at',
                    ],
                ],
            ]);
    });
});
