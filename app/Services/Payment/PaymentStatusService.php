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
use Illuminate\Support\Facades\Log;
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

            $targetStatus = $this->determineTargetStatus($result->outcome);

            $currentStatus = $payment->status;

            $transition = $this->determineTransition(
                $payment,
                $currentStatus,
                $targetStatus,
            );

            if ($transition !== PaymentStatusTransition::TRANSITIONED) {
                return $transition;
            }

            $previousStatus = $payment->status;

            $this->persistTransition($payment, $targetStatus);

            $this->logTransition(
                'Payment status transitioned',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'from' => $previousStatus->value,
                    'to' => $targetStatus->value,
                ],
                'info',
            );

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

    private function determineTargetStatus(PaymentOutcome $outcome): PaymentStatus
    {
        return match ($outcome) {
            PaymentOutcome::PENDING => PaymentStatus::PENDING,
            PaymentOutcome::SUCCESS => PaymentStatus::PAID,
            PaymentOutcome::FAILED => PaymentStatus::FAILED,
            PaymentOutcome::EXPIRED => PaymentStatus::EXPIRED,
            PaymentOutcome::CANCELLED => PaymentStatus::CANCELLED,
        };
    }

    private function determineTransition(
        Payment $payment,
        PaymentStatus $current,
        PaymentStatus $target,
    ): PaymentStatusTransition {
        if ($current === $target) {
            $this->logTransition(
                'Payment status transition idempotent',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $current->value,
                ],
                'info',
            );

            return PaymentStatusTransition::IDEMPOTENT;
        }

        if ($this->canTransition($current, $target)) {
            return PaymentStatusTransition::TRANSITIONED;
        }

        $this->logTransition(
            'Payment status transition conflict',
            [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'current_status' => $current->value,
                'requested_status' => $target->value,
            ],
            'warning',
        );

        return PaymentStatusTransition::CONFLICT;
    }

    private function canTransition(PaymentStatus $current, PaymentStatus $target): bool
    {
        return in_array(
            $target,
            self::ALLOWED_TRANSITIONS[$current->value] ?? [],
            true,
        );
    }

    private function persistTransition(Payment $payment, PaymentStatus $targetStatus): void
    {
        $payment->status = $targetStatus;

        if ($targetStatus === PaymentStatus::PAID) {
            $payment->paid_at = now();
        }

        $payment->save();
    }

    private function logTransition(string $message, array $context, string $level): void
    {
        match ($level) {
            'info' => Log::info($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
            default => Log::info($message, $context),
        };
    }
}
