<?php

namespace Tests\Feature\Services\Payment;

use App\Enum\OrderStatus;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Enum\PaymentStatusTransition;
use App\Events\PaymentPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentStatusService;
use App\DataTransferObjects\PaymentEventResult;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
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
        Event::fake([PaymentPaid::class]);

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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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

        Event::assertDispatched(PaymentPaid::class, function (PaymentPaid $event) use ($payment): bool {
            return $event->paymentId === $payment->id;
        });
    }

    public function test_pending_payment_can_transition_to_failed(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PENDING,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
        Event::fake([PaymentPaid::class]);

        $payment = $this->createPayment(
            status: PaymentStatus::PAID,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::IDEMPOTENT);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);

        Event::assertNotDispatched(PaymentPaid::class);
    }

    public function test_paid_payment_cannot_transition_to_failed(): void
    {
        $payment = $this->createPayment(
            status: PaymentStatus::PAID,
        );

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
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

    // #42 Reduce Stock

    private function createOrderItem(
        Order $order,
        Product $product,
        int $quantity,
    ) {
        $orderItem = OrderItem::factory()
            ->fromProduct($product)
            ->for($order)
            ->state([
                'quantity' => $quantity,
                'subtotal' => $product->price * $quantity,
            ])
            ->create();

        return $orderItem;
    }

    public function test_paid_order_reduces_product_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $product,
            quantity: 3,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $transition = $this->service->update($result);

        expect($transition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_paid_order_reduces_stock_for_all_order_items(): void
    {
        $productA = Product::factory()->create([
            'stock' => 10,
        ]);

        $productB = Product::factory()->create([
            'stock' => 20,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $productA,
            quantity: 2,
        );

        $this->createOrderItem(
            order: $order,
            product: $productB,
            quantity: 3,
        );;

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $this->service->update($result);

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'stock' => 17,
        ]);
    }

    public function test_paid_order_fails_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create([
            'stock' => 1,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $product,
            quantity: 2,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $this->expectException(ValidationException::class);

        $this->service->update($result);
    }

    public function test_paid_order_stock_reduction_is_atomic(): void
    {
        $productA = Product::factory()->create([
            'stock' => 10,
        ]);

        $productB = Product::factory()->create([
            'stock' => 1,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $productA,
            quantity: 2,
        );

        $this->createOrderItem(
            order: $order,
            product: $productB,
            quantity: 2,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        try {
            $this->service->update($result);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // Expected.
        }

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'stock' => 1,
        ]);
    }

    public function test_duplicate_paid_processing_does_not_reduce_stock_twice(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $product,
            quantity: 3,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::SUCCESS,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $firstTransition = $this->service->update($result);
        $secondTransition = $this->service->update($result);

        expect($firstTransition)
            ->toBe(PaymentStatusTransition::TRANSITIONED);

        expect($secondTransition)
            ->toBe(PaymentStatusTransition::IDEMPOTENT);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_failed_payment_does_not_reduce_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);

        $this->createOrderItem(
            order: $order,
            product: $product,
            quantity: 3,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PENDING,
            'amount' => 100_000,
        ]);

        $result = new PaymentEventResult(
            paymentId: $payment->id,
            outcome: PaymentOutcome::FAILED,
            gatewayTransactionId: null,
            paymentMethod: null,
            metadata: null,
        );

        $this->service->update($result);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }
}
