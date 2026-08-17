<?php

namespace App\Services\Order;

use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    public function update(Order $order, OrderStatus $targetStatus): OrderStatusTransition
    {
        $currentStatus = $order->status;

        $transition = $this->determineTransition(
            $currentStatus,
            $targetStatus,
        );

        if ($transition === OrderStatusTransition::IDEMPOTENT) {
            $this->logTransition(
                'Order status transition idempotent',
                [
                    'order_id' => $order->id,
                    'status' => $currentStatus->value,
                ],
                'info',
            );

            return $transition;
        }

        if ($transition === OrderStatusTransition::CONFLICT) {
            $this->logTransition(
                'Order status transition conflict',
                [
                    'order_id' => $order->id,
                    'current_status' => $currentStatus->value,
                    'requested_status' => $targetStatus->value,
                ],
                'warning',
            );

            return $transition;
        }

        $previousStatus = $order->status;

        $this->persistTransition($order, $targetStatus);

        $this->logTransition(
            'Order status transitioned',
            [
                'order_id' => $order->id,
                'from' => $previousStatus->value,
                'to' => $targetStatus->value,
            ],
            'info',
        );

        return $transition;
    }

    private function determineTransition(
        OrderStatus $current,
        OrderStatus $target,
    ): OrderStatusTransition {
        if ($current === $target) {
            return OrderStatusTransition::IDEMPOTENT;
        }

        if ($current->canTransitionTo($target)) {
            return OrderStatusTransition::TRANSITIONED;
        }

        return OrderStatusTransition::CONFLICT;
    }

    private function persistTransition(
        Order $order,
        OrderStatus $targetStatus,
    ): void {
        $order->status = $targetStatus;
        $order->save();
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
