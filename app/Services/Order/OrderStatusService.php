<?php

namespace App\Services\Order;

use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Models\Order;

class OrderStatusService
{
    public function update(Order $order, OrderStatus $targetStatus): OrderStatusTransition
    {
        $transition = $this->determineTransition(
            $order->status,
            $targetStatus,
        );

        if ($transition !== OrderStatusTransition::TRANSITIONED) {
            return $transition;
        }

        $this->persistTransition($order, $targetStatus);

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
