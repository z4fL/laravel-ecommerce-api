<?php

namespace App\Services\Payment;

use App\DataTransferObjects\PaymentEventResult;
use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Enum\PaymentStatusTransition;
use App\Models\Payment;
use App\Services\Order\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentStatusService
{

    public function __construct(
        private readonly OrderStatusService $orderStatusService,
    ) {}

    /**
     * @var array<string, array<int, PaymentStatus>>
     */
    private const ALLOWED_TRANSITIONS = [
        PaymentStatus::PENDING->value => [
            PaymentStatus::PAID,
            PaymentStatus::FAILED,
            PaymentStatus::EXPIRED,
            PaymentStatus::CANCELLED,
        ],
    ];

    public function update(PaymentEventResult $result): PaymentStatusTransition
    {
        return DB::transaction(function () use ($result) {

            $payment = Payment::query()
                ->with('order')
                ->findOrFail($result->paymentId);

            $targetStatus = $this->determineTargetStatus(
                $result->outcome,
            );

            $transition = $this->determineTransition(
                $payment->status,
                $targetStatus,
            );

            if ($transition !== PaymentStatusTransition::TRANSITIONED) {
                return $transition;
            }

            $this->persistTransition($payment, $targetStatus);

            if ($targetStatus === PaymentStatus::PAID) {
                $this->updateOrderStatus($payment);
            }

            return $transition;
        });
    }

    private function updateOrderStatus(Payment $payment): void
    {
        $transition = $this->orderStatusService->update(
            $payment->order,
            OrderStatus::PAID,
        );

        if ($transition === OrderStatusTransition::CONFLICT) {
            throw ValidationException::withMessages([
                'order' => 'Order status cannot be updated to paid.',
            ]);
        }
    }

    private function determineTargetStatus(
        PaymentOutcome $outcome,
    ): PaymentStatus {
        return match ($outcome) {
            PaymentOutcome::PENDING => PaymentStatus::PENDING,
            PaymentOutcome::SUCCESS => PaymentStatus::PAID,
            PaymentOutcome::FAILED => PaymentStatus::FAILED,
            PaymentOutcome::EXPIRED => PaymentStatus::EXPIRED,
            PaymentOutcome::CANCELLED => PaymentStatus::CANCELLED,
        };
    }

    private function determineTransition(
        PaymentStatus $current,
        PaymentStatus $target,
    ): PaymentStatusTransition {
        if ($current === $target) {
            return PaymentStatusTransition::IDEMPOTENT;
        }

        if ($this->canTransition($current, $target)) {
            return PaymentStatusTransition::TRANSITIONED;
        }

        return PaymentStatusTransition::CONFLICT;
    }

    private function canTransition(
        PaymentStatus $current,
        PaymentStatus $target,
    ): bool {
        return in_array(
            $target,
            self::ALLOWED_TRANSITIONS[$current->value] ?? [],
            true,
        );
    }

        private function persistTransition(
        Payment $payment,
        PaymentStatus $targetStatus,
    ): void {
        $payment->status = $targetStatus;

        if ($targetStatus === PaymentStatus::PAID) {
            $payment->paid_at = now();
        }

        $payment->save();
    }
}
