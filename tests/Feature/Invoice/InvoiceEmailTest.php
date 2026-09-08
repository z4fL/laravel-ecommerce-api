<?php

use App\Events\OrderPaid;
use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches invoice email job through listener when order paid', function () {
    $order = Order::factory()->create();

    Queue::fake();

    Event::dispatch(new OrderPaid(orderId: $order->id));

    Queue::assertPushed(SendInvoiceEmailJob::class, function (SendInvoiceEmailJob $job) use ($order) {
        return $job->orderId === $order->id;
    });
});

it('sends invoice email to order customer', function () {
    $user = User::factory()->create(['email' => 'customer@example.test']);
    $order = Order::factory()->create(['user_id' => $user->id]);

    Mail::fake();

    (new SendInvoiceEmailJob(orderId: $order->id))->handle();

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($order, $user) {
        return $mail->hasTo($user->email)
            && $mail->order->is($order);
    });
});

it('does nothing when order is missing', function () {
    Mail::fake();

    (new SendInvoiceEmailJob(orderId: 999999))->handle();

    Mail::assertNothingSent();
});
