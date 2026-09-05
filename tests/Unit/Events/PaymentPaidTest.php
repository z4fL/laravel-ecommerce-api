<?php

namespace Tests\Unit\Events;

use App\Events\PaymentPaid;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentPaidTest extends TestCase
{
    public function test_payment_paid_listener_handles_event(): void
    {
        Log::spy();

        Event::dispatch(new PaymentPaid(paymentId: 42));

        Log::shouldHaveReceived('info')
            ->once()
            ->with('PaymentPaid event handled', ['payment_id' => 42]);
    }
}
