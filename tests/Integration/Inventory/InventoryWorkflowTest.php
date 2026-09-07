<?php

use App\DataTransferObjects\PaymentEventResult;
use App\Enum\InventoryHistoryType;
use App\Enum\OrderStatus;
use App\Enum\PaymentOutcome;
use App\Enum\PaymentStatus;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('decreases stock and records the before and after values', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::factory()->create();

    app(InventoryService::class)->decreaseStock($product, 3, $order);

    expect($product->fresh()->stock)->toBe(7);
    expect(InventoryHistory::query()->sole()->toArray())->toMatchArray([
        'product_id' => $product->id,
        'order_id' => $order->id,
        'type' => InventoryHistoryType::DECREASE->value,
        'quantity' => 3,
        'stock_before' => 10,
        'stock_after' => 7,
    ]);
});

it('restores stock and records an increase history entry', function () {
    $product = Product::factory()->create(['stock' => 7]);

    app(InventoryService::class)->increaseStock($product, 3);

    expect($product->fresh()->stock)->toBe(10);
    expect(InventoryHistory::query()->sole()->type)->toBe(InventoryHistoryType::INCREASE);
    expect(InventoryHistory::query()->sole()->stock_before)->toBe(7);
    expect(InventoryHistory::query()->sole()->stock_after)->toBe(10);
});

it('prevents an insufficient decrease without changing stock or history', function () {
    $product = Product::factory()->create(['stock' => 2]);

    expect(fn () => app(InventoryService::class)->decreaseStock($product, 3))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock)->toBe(2)
        ->and(InventoryHistory::query()->count())->toBe(0);
});

it('rolls back stock and history with the surrounding transaction', function () {
    $product = Product::factory()->create(['stock' => 10]);

    expect(fn () => DB::transaction(function () use ($product) {
        app(InventoryService::class)->decreaseStock($product, 3);
        throw new RuntimeException('rollback inventory workflow');
    }))->toThrow(RuntimeException::class, 'rollback inventory workflow');

    expect($product->fresh()->stock)->toBe(10)
        ->and(InventoryHistory::query()->count())->toBe(0);
});

it('reduces stock once when payment succeeds and restores it when the order is cancelled', function () {
    Event::fake();

    $product = Product::factory()->create(['stock' => 8]);
    $order = Order::factory()->create(['status' => OrderStatus::PENDING_PAYMENT]);
    OrderItem::factory()->fromProduct($product)->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'gateway_order_id' => 'gateway-order',
        'status' => PaymentStatus::PENDING,
        'amount' => 100_000,
    ]);

    app(PaymentStatusService::class)->update(new PaymentEventResult(
        paymentId: $payment->id,
        outcome: PaymentOutcome::SUCCESS,
        gatewayTransactionId: 'transaction-id',
        paymentMethod: 'bank_transfer',
        metadata: null,
    ));

    expect($product->fresh()->stock)->toBe(5)
        ->and(InventoryHistory::query()->where('type', InventoryHistoryType::DECREASE)->count())->toBe(1);

    app(OrderService::class)->cancel($order->fresh());

    expect($product->fresh()->stock)->toBe(8)
        ->and(InventoryHistory::query()->where('type', InventoryHistoryType::INCREASE)->count())->toBe(1);
});
