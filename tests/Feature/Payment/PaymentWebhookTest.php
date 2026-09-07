<?php

use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function webhookPayload(Payment $payment, array $overrides = []): array
{
    $payload = array_merge([
        'order_id' => $payment->gateway_order_id,
        'status_code' => '200',
        'gross_amount' => (string) $payment->amount,
        'transaction_id' => 'transaction-1',
        'transaction_status' => 'settlement',
        'payment_type' => 'bank_transfer',
        'currency' => 'IDR',
    ], $overrides);

    if (! array_key_exists('signature_key', $overrides)) {
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('payment.midtrans.server_key')
        );
    }

    return $payload;
}

function webhookPayment(array $attributes = []): Payment
{
    $order = Order::factory()->create(['status' => OrderStatus::PENDING_PAYMENT]);

    return Payment::create(array_merge([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'gateway_order_id' => 'webhook-order',
        'status' => PaymentStatus::PENDING,
        'amount' => 100_000,
    ], $attributes));
}

beforeEach(function () {
    config(['payment.midtrans.server_key' => 'test-server-key']);
    Queue::fake();
});

it('processes a valid payment webhook and updates the order', function () {
    $payment = webhookPayment();

    $this->postJson('/api/v1/webhook/payment/midtrans', webhookPayload($payment))
        ->assertOk()
        ->assertJsonPath('data.payment_id', $payment->id)
        ->assertJsonPath('data.outcome', 'success')
        ->assertJsonPath('data.transition', 'transitioned');

    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID)
        ->and($payment->order->fresh()->status)->toBe(OrderStatus::PAID);
});

it('returns an idempotent result for a duplicate webhook', function () {
    $payment = webhookPayment(['status' => PaymentStatus::PAID]);
    $payment->order->update(['status' => OrderStatus::PAID]);

    $payload = webhookPayload($payment);

    $this->postJson('/api/v1/webhook/payment/midtrans', $payload)
        ->assertOk()
        ->assertJsonPath('data.transition', 'idempotent');
});

it('rejects an invalid webhook signature before processing the event', function () {
    $payment = webhookPayment();

    $this->postJson('/api/v1/webhook/payment/midtrans', webhookPayload($payment, [
        'signature_key' => 'invalid-signature',
    ]))->assertUnauthorized();

    expect($payment->fresh()->status)->toBe(PaymentStatus::PENDING);
});

it('rejects an incomplete webhook payload', function () {
    $this->postJson('/api/v1/webhook/payment/midtrans', [
        'order_id' => 'missing-fields',
    ])->assertUnauthorized();
});

it('rejects an unsupported payment gateway', function () {
    $this->postJson('/api/v1/webhook/payment/unknown', [])->assertBadRequest();
});
