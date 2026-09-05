<?php

namespace Tests\Feature\AuditLog;

use App\Events\PaymentPaid;
use App\Enum\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RecordAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_paid_records_audit_log_with_order_owner_as_actor(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'status' => PaymentStatus::PAID,
            'amount' => 100_000,
        ]);

        Event::dispatch(new PaymentPaid(paymentId: $payment->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'payment.paid',
            'resource_type' => 'Payment',
            'resource_id' => $payment->id,
        ]);

        $this->assertSame($user->id, AuditLog::query()->latest('id')->value('user_id'));
    }
}