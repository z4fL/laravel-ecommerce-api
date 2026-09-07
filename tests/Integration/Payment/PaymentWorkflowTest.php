<?php

use App\DataTransferObjects\PaymentEventResult;
use App\Contracts\PaymentWebhookInterface;
use App\Enum\OrderStatus;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Enum\PaymentStatusTransition;
use App\Events\OrderPaid;
use App\Events\PaymentPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentEventProcessor;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function paymentForWorkflow(array $attributes = []): Payment
{
    $order = Order::factory()->create([
        'status' => OrderStatus::PENDING_PAYMENT,
    ]);

    return Payment::create(array_merge([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'gateway_order_id' => 'gateway-order',
        'status' => PaymentStatus::PENDING,
        'amount' => 100_000,
    ], $attributes));
}

it('processes a successful payment atomically with its order and events', function () {
    Event::fake([PaymentPaid::class, OrderPaid::class]);
    $payment = paymentForWorkflow();

    $result = new PaymentEventResult(
        paymentId: $payment->id,
        outcome: PaymentOutcome::SUCCESS,
        gatewayTransactionId: 'gateway-transaction',
        paymentMethod: 'bank_transfer',
        metadata: ['currency' => 'IDR'],
    );

    $transition = app(PaymentStatusService::class)->update($result);

    expect($transition)->toBe(PaymentStatusTransition::TRANSITIONED);
    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID)
        ->and($payment->fresh()->gateway_transaction_id)->toBe('gateway-transaction')
        ->and($payment->fresh()->payment_method)->toBe('bank_transfer')
        ->and($payment->fresh()->paid_at)->not->toBeNull();
    expect($payment->order->fresh()->status)->toBe(OrderStatus::PAID);
    Event::assertDispatched(PaymentPaid::class);
    Event::assertDispatched(OrderPaid::class);
});

it('does not repeat side effects for a duplicate payment event', function () {
    Event::fake([PaymentPaid::class, OrderPaid::class]);
    $payment = paymentForWorkflow(['status' => PaymentStatus::PAID]);

    $result = new PaymentEventResult(
        paymentId: $payment->id,
        outcome: PaymentOutcome::SUCCESS,
        gatewayTransactionId: 'duplicate-transaction',
        paymentMethod: 'bank_transfer',
        metadata: ['currency' => 'IDR'],
    );

    expect(app(PaymentStatusService::class)->update($result))
        ->toBe(PaymentStatusTransition::IDEMPOTENT);
    Event::assertNotDispatched(PaymentPaid::class);
    Event::assertNotDispatched(OrderPaid::class);
    expect($payment->fresh()->gateway_transaction_id)->toBeNull();
});

it('keeps a paid payment unchanged when a later failure conflicts', function () {
    $payment = paymentForWorkflow(['status' => PaymentStatus::PAID]);

    $result = new PaymentEventResult(
        paymentId: $payment->id,
        outcome: PaymentOutcome::FAILED,
        gatewayTransactionId: null,
        paymentMethod: null,
        metadata: null,
    );

    expect(app(PaymentStatusService::class)->update($result))
        ->toBe(PaymentStatusTransition::CONFLICT);
    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID);
});

it('rejects a webhook event for an unknown payment or mismatched amount', function () {
    $payment = paymentForWorkflow();
    $gateway = mock(PaymentWebhookInterface::class, function ($gateway) {
        $gateway->shouldReceive('determineOutcome')->never();
    });
    $processor = app(PaymentEventProcessor::class);

    expect(fn () => $processor->process([
        'gateway' => 'midtrans',
        'order_id' => 'missing-order',
        'gross_amount' => $payment->amount,
        'status' => 'settlement',
        'transaction_id' => 'transaction',
        'payment_type' => 'bank_transfer',
        'currency' => 'IDR',
        'raw_payload' => [],
    ], $gateway))->toThrow(ValidationException::class);

    expect(fn () => $processor->process([
        'gateway' => 'midtrans',
        'order_id' => $payment->gateway_order_id,
        'gross_amount' => $payment->amount + 1,
        'status' => 'settlement',
        'transaction_id' => 'transaction',
        'payment_type' => 'bank_transfer',
        'currency' => 'IDR',
        'raw_payload' => [],
    ], $gateway))->toThrow(ValidationException::class);
});
