<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Enum\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PaymentService
{

    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function create(Order $order): Payment
    {
        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => 'Order is not eligible for payment.',
            ]);
        }

        return DB::transaction(function () use ($order) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => config('payment.default'),
                'gateway_order_id' => (string) Str::ulid(),
                'status' => PaymentStatus::PENDING,
                'amount' => $order->total,
            ]);

            Log::info('Payment gateway request sent', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $payment->gateway,
                'gateway_order_id' => $payment->gateway_order_id,
                'amount' => $payment->amount,
            ]);

            $response = $this->paymentGateway->createTransaction(
                $payment->load(['order.orderItems', 'order.user'])
            );

            $errorMessages = data_get($response, 'error_messages');

            if (! empty($errorMessages)) {
                Log::error('Payment gateway request failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'gateway' => $payment->gateway,
                    'error' => is_array($errorMessages)
                        ? implode(' ', $errorMessages)
                        : (string) $errorMessages,
                ]);

                throw ValidationException::withMessages([
                    'payment' => is_array($errorMessages)
                        ? implode(' ', $errorMessages)
                        : (string) $errorMessages,
                ]);
            }

            $payment->update([
                'gateway_transaction_id' => $response['transaction_id'],
                'payment_url' => $response['redirect_url'],
                'expired_at' => now()->addMinutes(29),
                'metadata' => [
                    'snap_token' => $response['token']
                ]
            ]);

            return $payment;
        });
    }
}
