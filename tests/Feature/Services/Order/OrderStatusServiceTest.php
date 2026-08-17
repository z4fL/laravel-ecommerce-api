<?php

namespace Tests\Feature\Services\Order;

use App\Enum\OrderStatus;
use App\Enum\OrderStatusTransition;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrderStatusService::class);
    }

    public function test_pending_payment_can_transition_to_paid(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PAID,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_pending_payment_can_transition_to_cancelled(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::CANCELLED,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_paid_order_can_transition_to_processing(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PROCESSING,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PROCESSING->value,
        ]);
    }

    public function test_paid_order_can_transition_to_cancelled(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::CANCELLED,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_processing_order_can_transition_to_shipped(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::SHIPPED,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::SHIPPED->value,
        ]);
    }

    public function test_shipped_order_can_transition_to_completed(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::SHIPPED,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::COMPLETED,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::COMPLETED->value,
        ]);
    }

    public function test_same_order_status_is_idempotent(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PAID,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::IDEMPOTENT);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_paid_order_cannot_transition_back_to_pending_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PENDING_PAYMENT,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::CONFLICT);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_completed_order_cannot_transition_to_paid(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PAID,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::CONFLICT);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::COMPLETED->value,
        ]);
    }

    public function test_cancelled_order_cannot_transition_to_paid(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::CANCELLED,
        ]);

        $transition = $this->service->update(
            $order,
            OrderStatus::PAID,
        );

        expect($transition)
            ->toBe(OrderStatusTransition::CONFLICT);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }
}
