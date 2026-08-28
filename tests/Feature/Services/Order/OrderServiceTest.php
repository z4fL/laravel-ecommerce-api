<?php

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrderService::class);
    }

    public function test_pending_payment_order_restores_stock_when_cancelled(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        OrderItem::factory()
            ->fromProduct($product)
            ->create([
                'order_id' => $order->id,
                'quantity' => 3,
            ]);

        $this->service->cancel($order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 13,
        ]);
    }

    public function test_paid_order_restores_stock_when_cancelled(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        OrderItem::factory()
            ->fromProduct($product)
            ->create([
                'order_id' => $order->id,
                'quantity' => 3,
            ]);

        $this->service->cancel($order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 13,
        ]);
    }

    public function test_all_order_items_are_restored_when_order_is_cancelled(): void
    {
        $productA = Product::factory()->create([
            'stock' => 10,
        ]);

        $productB = Product::factory()->create([
            'stock' => 20,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        OrderItem::factory()
            ->fromProduct($productA)
            ->create([
                'order_id' => $order->id,
                'quantity' => 2,
            ]);

        OrderItem::factory()
            ->fromProduct($productB)
            ->create([
                'order_id' => $order->id,
                'quantity' => 5,
            ]);

        $this->service->cancel($order);

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'stock' => 12,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'stock' => 25,
        ]);
    }

    public function test_cancelled_order_does_not_restore_stock_again(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::CANCELLED,
        ]);

        OrderItem::factory()
            ->fromProduct($product)
            ->create([
                'order_id' => $order->id,
                'quantity' => 3,
            ]);

        $this->service->cancel($order);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_order_that_cannot_be_cancelled_does_not_restore_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED,
        ]);

        OrderItem::factory()
            ->fromProduct($product)
            ->create([
                'order_id' => $order->id,
                'quantity' => 3,
            ]);

        $this->expectException(ValidationException::class);

        $this->service->cancel($order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }
}
