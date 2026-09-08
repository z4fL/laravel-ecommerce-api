<?php

use App\Enum\PaymentStatus;
use App\Events\PaymentPaid;
use App\Listeners\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues audit log listener when payment paid event is dispatched', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'status' => PaymentStatus::PAID,
        'amount' => 100_000,
    ]);

    Queue::fake();

    Event::dispatch(new PaymentPaid(paymentId: $payment->id));

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
        return $job->class === RecordAuditLog::class;
    });

    $this->assertDatabaseMissing('audit_logs', [
        'resource_id' => $payment->id,
    ]);
});

it('processes queued audit log listener and writes audit log record', function () {
    config(['queue.connections.redis.queue' => 'audit-log-test-'.uniqid()]);

    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'gateway' => 'midtrans',
        'status' => PaymentStatus::PAID,
        'amount' => 100_000,
    ]);

    Event::dispatch(new PaymentPaid(paymentId: $payment->id));

    $this->artisan('queue:work', ['--once' => true])
        ->assertExitCode(0);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'payment.paid',
        'resource_type' => 'Payment',
        'resource_id' => $payment->id,
    ]);

    expect(AuditLog::query()->latest('id')->value('user_id'))->toBe($user->id);
});
