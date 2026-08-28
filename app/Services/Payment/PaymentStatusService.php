<?php

namespace App\Services\Payment;

use App\DataTransferObjects\PaymentEventResult;
use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Enum\PaymentStatusTransition;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\Order\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentStatusService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function update(PaymentEventResult $result): PaymentStatusTransition
    {
        return DB::transaction(function () use ($result) {
            $payment = Payment::query()
                ->with('order')
                ->lockForUpdate()
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

            $this->persistEventData($payment, $result);

            $previousStatus = $payment->status;

            $this->persistTransition($payment, $targetStatus);

            Log::info(
                'Payment status transitioned',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'from' => $previousStatus->value,
                    'to' => $targetStatus->value,
                ]
            );

            if ($targetStatus === PaymentStatus::PAID) {
                $orderTransition = $this->updateOrderStatus($payment);

                if ($orderTransition === OrderStatusTransition::TRANSITIONED) {
                    $this->reduceOrderStock($payment->order);
                }
            }

            return $transition;
        });
    }

    private function persistEventData(
        Payment $payment,
        PaymentEventResult $result,
    ): void {
        if (
            $payment->gateway_transaction_id === null
            && $result->gatewayTransactionId !== null
        ) {
            $payment->gateway_transaction_id = $result->gatewayTransactionId;
        }

        if (
            $payment->payment_method === null
            && $result->paymentMethod !== null
        ) {
            $payment->payment_method = $result->paymentMethod;
        }

        if (
            $payment->metadata === null
            && $result->metadata !== null
        ) {
            $payment->metadata = $result->metadata;
        }

        $payment->save();
    }

    private function updateOrderStatus(Payment $payment): OrderStatusTransition
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

        return $transition;
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
            Log::info(
                'Payment status transition idempotent',
                [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $current->value,
                ]
            );

            return PaymentStatusTransition::IDEMPOTENT;
        }

        if ($current->canTransitionTo($target)) {
            return PaymentStatusTransition::TRANSITIONED;
        }

        Log::warning(
            'Payment status transition conflict',
            [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'current_status' => $current->value,
                'requested_status' => $target->value,
            ]
        );

        return PaymentStatusTransition::CONFLICT;
    }

    private function persistTransition(
        Payment $payment,
        PaymentStatus $targetStatus
    ): void {
        $payment->status = $targetStatus;

        if ($targetStatus === PaymentStatus::PAID) {
            $payment->paid_at = now();
        }

        $payment->save();
    }

    // Inventory Service
    private function reduceOrderStock(Order $order): void
    {
        $order->loadMissing('orderItems.product');

        foreach ($order->orderItems as $orderItem) {
            $this->inventoryService->decreaseStock(
                $orderItem->product,
                $orderItem->quantity,
            );
        }
    }
}
