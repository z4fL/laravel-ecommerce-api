<?php

namespace Tests\Feature\AuditLog;

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
use Tests\TestCase;

class RecordAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_paid_queues_audit_log_listener(): void
    {
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

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return $job->class === RecordAuditLog::class;
        });

        $this->assertDatabaseMissing('audit_logs', [
            'resource_id' => $payment->id,
        ]);
    }

    public function test_worker_processes_queued_audit_log_listener(): void
    {
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

        $this->assertSame($user->id, AuditLog::query()->latest('id')->value('user_id'));
    }
}
