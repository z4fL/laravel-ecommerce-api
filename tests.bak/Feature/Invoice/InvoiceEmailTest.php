<?php

namespace Tests\Feature\Invoice;

use App\Events\OrderPaid;
use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_paid_dispatches_invoice_email_job_through_listener(): void
    {
        $order = Order::factory()->create();

        Queue::fake();

        Event::dispatch(new OrderPaid(orderId: $order->id));

        Queue::assertPushed(SendInvoiceEmailJob::class, function (SendInvoiceEmailJob $job) use ($order): bool {
            return $job->orderId === $order->id;
        });
    }

    public function test_invoice_email_job_sends_to_order_customer(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.test']);
        $order = Order::factory()->create(['user_id' => $user->id]);

        Mail::fake();

        (new SendInvoiceEmailJob(orderId: $order->id))->handle();

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($order, $user): bool {
            return $mail->hasTo($user->email)
                && $mail->order->is($order);
        });
    }

    public function test_invoice_email_job_does_nothing_when_order_is_missing(): void
    {
        Mail::fake();

        (new SendInvoiceEmailJob(orderId: 999999))->handle();

        Mail::assertNothingSent();
    }
}
