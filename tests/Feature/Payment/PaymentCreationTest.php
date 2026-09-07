<?php

use App\Contracts\PaymentGatewayInterface;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\PaymentGateways\Enums\PaymentGatewayErrorType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to create a payment', function () {
    $order = Order::factory()->create();

    $this->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertUnauthorized();
});

it('requires a verified customer to create a payment', function () {
    $customer = User::factory()->unverified()->create();
    $order = Order::factory()->for($customer)->create();

    $this->actingAs($customer, 'api')
        ->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertForbidden();
});

it('creates a pending payment and stores the gateway response', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->for($customer)->create(['total' => 125_000]);

    $this->mock(PaymentGatewayInterface::class, function ($gateway) {
        $gateway->shouldReceive('createTransaction')
            ->once()
            ->andReturn([
                'success' => true,
                'redirect_url' => 'https://gateway.test/pay',
                'token' => 'snap-token',
            ]);
    });

    $this->actingAs($customer, 'api')
        ->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertCreated()
        ->assertJsonPath('data.status', PaymentStatus::PENDING->value)
        ->assertJsonPath('data.amount', 125_000)
        ->assertJsonPath('data.payment_url', 'https://gateway.test/pay')
        ->assertJsonPath('data.snap_token', 'snap-token');

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING->value,
        'amount' => 125_000,
    ]);
});

it('reuses an active pending payment without calling the gateway again', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->for($customer)->create();
    $payment = Payment::create([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'gateway_order_id' => 'existing-order',
        'status' => PaymentStatus::PENDING,
        'amount' => $order->total,
        'expired_at' => now()->addMinutes(10),
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($gateway) {
        $gateway->shouldNotReceive('createTransaction');
    });

    $this->actingAs($customer, 'api')
        ->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertCreated()
        ->assertJsonPath('data.id', $payment->id);

    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('rejects payment creation for an order that is no longer awaiting payment', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::PAID]);

    $this->mock(PaymentGatewayInterface::class, function ($gateway) {
        $gateway->shouldNotReceive('createTransaction');
    });

    $this->actingAs($customer, 'api')
        ->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment');
});

it('marks the payment and order failed for a non-retryable gateway error', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->for($customer)->create();

    $this->mock(PaymentGatewayInterface::class, function ($gateway) {
        $gateway->shouldReceive('createTransaction')
            ->once()
            ->andReturn([
                'success' => false,
                'status_code' => 400,
                'error_type' => PaymentGatewayErrorType::CLIENT_ERROR,
                'error_messages' => ['Invalid payment request.'],
            ]);
    });

    $this->actingAs($customer, 'api')
        ->postJson("/api/v1/orders/{$order->id}/payment")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment');

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => PaymentStatus::FAILED->value,
    ]);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::PAYMENT_FAILED->value,
    ]);
});
