<?php

namespace App\Listeners;

use App\Events\PaymentPaid;
use App\Models\AuditLog;
use App\Models\Payment;

final class RecordAuditLog
{
    public function handle(PaymentPaid $event): void
    {
        $payment = Payment::with('order.user')->find($event->paymentId);

        if ($payment === null) {
            return;
        }

        AuditLog::create([
            'user_id' => $payment->order?->user_id,
            'action' => 'payment.paid',
            'resource_type' => 'Payment',
            'resource_id' => $payment->id,
        ]);
    }
}