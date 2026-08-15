<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookInterface;
use App\Enum\PaymentOutcome;
use App\Models\Payment;
use BadMethodCallException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MidtransGateway implements PaymentGatewayInterface, PaymentWebhookInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        Config::$serverKey = config('payment.midtrans.server_key');
        Config::$clientKey = config('payment.midtrans.client_key');
        Config::$isProduction = config('payment.midtrans.is_production');
        Config::$isSanitized = config('payment.midtrans.is_sanitized');
        Config::$is3ds = config('payment.midtrans.is_3ds');
    }

    private function splitName(?string $fullName): array
    {
        $name = trim($fullName ?? '');

        if (empty($name)) {
            return ['first_name' => '', 'last_name' => ''];
        }

        // Split by any amount of whitespace
        $parts = preg_split('/\s+/', $name);
        $lastName = array_pop($parts);
        $firstName = !empty($parts) ? implode(' ', $parts) : $lastName;

        return [$firstName, $lastName];
    }

    public function createTransaction(Payment $payment): array
    {
        [$firstName, $lastName] = $this->splitName($payment->order?->recipient_name);

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->gateway_order_id,
                'gross_amount' => $payment->amount
            ],
            'customer_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $payment->order->user->email,
                'phone' => $payment->order->phone,
                'shipping_address' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $payment->order->phone,
                    'address' => $payment->order->address,
                    'city' => $payment->order->city,
                    'postal_code' => $payment->order->postal_code,
                    'country_code' => 'IDN'
                ]
            ],
            'expiry' => [
                "unit" => "minutes",
                "duration" => 30
            ]
        ];

        $response = Snap::createTransaction($payload);

        if (is_object($response) && ! empty($response->error_messages)) {
            return [
                'error_messages' => (array) $response->error_messages,
            ];
        }

        if (is_array($response) && ! empty($response['error_messages'])) {
            return [
                'error_messages' => (array) $response['error_messages'],
            ];
        }

        return [
            'redirect_url' => data_get($response, 'redirect_url'),
            'token' => data_get($response, 'token'),
        ];
    }

    public function getTransaction(string $transactionId): array
    {
        throw new BadMethodCallException(
            'getTransaction is not implemented yet.'
        );
    }

    public function cancelTransaction(string $transactionId): array
    {
        throw new BadMethodCallException(
            'cancelTransaction is not implemented yet.'
        );
    }

    public function verify(Request $request): void
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (
            $orderId === null ||
            $statusCode === null ||
            $grossAmount === null ||
            $signatureKey === null
        ) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid webhook signature.'
            );
        }

        $expectedSignature = hash(
            'sha512',
            $orderId
                . $statusCode
                . $grossAmount
                . config('payment.midtrans.server_key')
        );

        if (! hash_equals($expectedSignature, $signatureKey)) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid webhook signature.'
            );
        }
    }

    public function normalize(Request $request): array
    {
        $payload = $request->all();

        return [
            'gateway' => 'midtrans',
            'transaction_id' => $payload['transaction_id'],
            'order_id' => $payload['order_id'],
            'status' => $payload['transaction_status'],
            'payment_type' => $payload['payment_type'] ?? null,
            'gross_amount' => $payload['gross_amount'],
            'currency' => $payload['currency'] ?? null,
            'raw_payload' => $payload,
        ];
    }

    public function determineOutcome(string $status): PaymentOutcome
    {
        return match ($status) {
            'pending' => PaymentOutcome::PENDING,

            'capture',
            'settlement' => PaymentOutcome::SUCCESS,

            'deny',
            'failure' => PaymentOutcome::FAILED,

            'expire' => PaymentOutcome::EXPIRED,

            'cancel' => PaymentOutcome::CANCELLED,

            default => throw new InvalidArgumentException(
                "Unsupported Midtrans transaction status: {$status}"
            ),
        };
    }
}
