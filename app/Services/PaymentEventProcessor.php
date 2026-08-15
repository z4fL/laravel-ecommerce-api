<?php

namespace App\Services;

use App\Contracts\PaymentWebhookInterface;
use App\DataTransferObjects\PaymentEventResult;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class PaymentEventProcessor
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly PaymentWebhookInterface $paymentWebhook,
    ) {}

    public function process(array $event): PaymentEventResult
    {
        $payment = Payment::query()
            ->where('gateway', $event['gateway'])
            ->where('gateway_transaction_id', $event['transaction_id'])
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

        $outcome = $this->paymentWebhook->determineOutcome(
            $event['status']
        );

        return new PaymentEventResult(
            paymentId: $payment->id,
            outcome: $outcome,
        );
    }
}
