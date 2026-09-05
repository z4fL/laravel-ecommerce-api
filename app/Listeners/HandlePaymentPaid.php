<?php

namespace App\Listeners;

use App\Events\PaymentPaid;
use Illuminate\Support\Facades\Log;

final class HandlePaymentPaid
{
    public function handle(PaymentPaid $event): void
    {
        Log::info('PaymentPaid event handled', [
            'payment_id' => $event->paymentId,
        ]);
    }
}
