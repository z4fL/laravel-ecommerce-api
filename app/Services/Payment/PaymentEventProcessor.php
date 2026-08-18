<?php

namespace App\Services\Payment;

use App\Contracts\PaymentWebhookInterface;
use App\DataTransferObjects\PaymentEventResult;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class PaymentEventProcessor
{

    public function process(array $event, PaymentWebhookInterface $gateway): PaymentEventResult
    {
        $payment = Payment::query()
            ->where('gateway', $event['gateway'])
            ->where('gateway_order_id', $event['order_id'])
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'payment' => 'Payment transaction could not be resolved.',
            ]);
        }

        if ($payment->gateway_order_id !== $event['order_id']) {
            throw ValidationException::withMessages([
                'payment' => 'Payment gateway order ID does not match.',
            ]);
        }

        if ((int) $payment->amount !== (int) $event['gross_amount']) {
            throw ValidationException::withMessages([
                'payment' => 'Payment amount does not match.',
            ]);
        }

        $outcome = $gateway->determineOutcome(
            $event['status']
        );

        return new PaymentEventResult(
            paymentId: $payment->id,
            outcome: $outcome,
            gatewayTransactionId: $event['transaction_id'],
            paymentMethod: $event['payment_type'],
            metadata: [
                'currency' => $event['currency'],
                'raw_payload' => $event['raw_payload'],
            ],
        );
    }
}
