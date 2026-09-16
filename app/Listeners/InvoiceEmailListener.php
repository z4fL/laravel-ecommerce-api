<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SendInvoiceEmailJob;
use Illuminate\Support\Facades\Log;


final class InvoiceEmailListener
{
    public function handle(OrderPaid $event): void
    {
        try {
            SendInvoiceEmailJob::dispatch($event->orderId);
        } catch (\Throwable $e) {
            Log::error('Failed to send invoice email', [
                'order_id' => $event->orderId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
