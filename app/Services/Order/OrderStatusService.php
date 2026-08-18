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
            Log::info(
                'Order status transition idempotent',
                [
                    'order_id' => $order->id,
                    'status' => $currentStatus->value,
                ]
            );
            return $transition;
        }

        if ($transition === OrderStatusTransition::CONFLICT) {
            Log::warning(
                'Order status transition conflict',
                [
                    'order_id' => $order->id,
                    'current_status' => $currentStatus->value,
                    'requested_status' => $targetStatus->value,
                ]
            );

            return $transition;
        }

        $previousStatus = $order->status;

        $this->persistTransition($order, $targetStatus);

        Log::info(
            'Order status transitioned',
            [
                'order_id' => $order->id,
                'from' => $previousStatus->value,
                'to' => $targetStatus->value,
            ]
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
}
