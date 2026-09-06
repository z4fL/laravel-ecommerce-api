<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SendInvoiceEmailJob;

final class InvoiceEmailListener
{
    public function handle(OrderPaid $event): void
    {
        SendInvoiceEmailJob::dispatch($event->orderId);
    }
}
