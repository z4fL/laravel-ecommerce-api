<?php

namespace Tests\Feature\Services\Payment;

use App\Enum\OrderStatus;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Enum\PaymentStatusTransition;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentStatusService;
use App\DataTransferObjects\PaymentEventResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentStatusService::class);
    }

    public function test_pending_payment_can_transition_to_paid(): void
    {
        $order = Order::factory()
            ->create([
                'status' => OrderStatus::PENDING_PAYMENT,
            ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_pending_payment_can_transition_to_failed(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::FAILED->value,
        ]);
    }

    public function test_pending_payment_can_transition_to_expired(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::EXPIRED,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::EXPIRED->value,
        ]);
    }

    public function test_pending_payment_can_transition_to_cancelled(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::CANCELLED,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::CANCELLED->value,
        ]);
    }

    public function test_same_payment_status_is_idempotent(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PAID,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::IDEMPOTENT);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_paid_payment_cannot_transition_to_failed(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PAID,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::CONFLICT);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_paid_payment_cannot_transition_to_expired(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PAID,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::EXPIRED,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::CONFLICT);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_paid_transition_sets_paid_at(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        expect($payment->paid_at)->toBeNull();

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
        );

        $this->service->update($result);

        $payment->refresh();

        expect($payment->status)
            ->toBe(PaymentStatus::PAID);

        expect($payment->paid_at)
            ->not->toBeNull();
    }

    public function test_non_paid_transition_does_not_set_paid_at(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
        );

        $this->service->update($result);

        $payment->refresh();

        expect($payment->status)
            ->toBe(PaymentStatus::FAILED);

        expect($payment->paid_at)
            ->toBeNull();
    }

    private function createPayment(
        PaymentStatus $status,
    ): Payment {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => $status,
            'amount' => 100_000,
        ]);
    }
}
