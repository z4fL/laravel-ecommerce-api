<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Enum\PaymentStatus;
use App\PaymentGateways\Enums\PaymentGatewayErrorType;
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

        $payment = DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status !== OrderStatus::PENDING_PAYMENT) {
                throw ValidationException::withMessages([
                    'payment' => 'Order is not eligible for payment.',
                ]);
            }

            $activePayment = $order->payments()
                ->where('status', PaymentStatus::PENDING)
                ->where('expired_at', '>', now())
                ->latest('created_at')
                ->first();

            if ($activePayment) {
                return $activePayment;
            }

            return Payment::create([
                'order_id' => $order->id,
                'gateway' => config('payment.default'),
                'gateway_order_id' => (string) Str::ulid(),
                'status' => PaymentStatus::PENDING,
                'amount' => $order->total,
            ]);
        });

        if (! $payment->wasRecentlyCreated) {
            return $payment;
        }

        Log::info('Payment gateway request sent', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'gateway' => $payment->gateway,
            'gateway_order_id' => $payment->gateway_order_id,
            'amount' => $payment->amount,
        ]);

        $response = $this->paymentGateway->createTransaction(
            $payment->load(['order.orderItems'])
        );

        if (! $response['success']) {
            $this->handleGatewayFailure($payment, $response);
        }

        return DB::transaction(function () use ($payment, $response) {
            $payment->update([
                'payment_url' => $response['redirect_url'],
                'expired_at' => now()->addMinutes(30),
                'metadata' => [
                    'snap_token' => $response['token']
                ]
            ]);

            return $payment;
        });
    }

    /**
     * @return never
     */
    private function handleGatewayFailure(Payment $payment, array $response): void
    {
        /** @var PaymentGatewayErrorType $errorType */
        $errorType = $response['error_type'];
        $isRetryable = $errorType->isRetryable();

        DB::transaction(function () use ($payment, $response, $errorType, $isRetryable) {
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'metadata' => [
                    'gateway_error_status_code' => $response['status_code'],
                    'gateway_error_type' => $errorType->value,
                    'gateway_error_messages' => $response['error_messages'],
                    'is_retryable' => $isRetryable,
                ],
            ]);

            if (! $isRetryable) {
                // Guard: hanya ubah status Order kalau masih PENDING_PAYMENT.
                // Mencegah race condition menimpa status Order yang mungkin sudah
                // berubah lewat webhook di antara request ini berjalan.
                Order::query()
                    ->where('id', $payment->order_id)
                    ->where('status', OrderStatus::PENDING_PAYMENT)
                    ->update(['status' => OrderStatus::PAYMENT_FAILED]);
            }
        });

        $logContext = [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'gateway' => $payment->gateway,
            'status_code' => $response['status_code'],
            'error_type' => $errorType->value,
            'is_retryable' => $isRetryable,
            'error' => implode(' ', $response['error_messages']),
        ];

        if ($errorType === PaymentGatewayErrorType::CONFIGURATION) {
            Log::critical('Payment gateway misconfigured', $logContext);
        } else {
            Log::error('Payment gateway request failed', $logContext);
        }

        throw ValidationException::withMessages([
            'payment' => $isRetryable
                ? 'Payment gateway is currently unavailable, please try again shortly.'
                : implode(' ', $response['error_messages']),
        ]);
    }
}
