<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookInterface;
use App\Enum\PaymentOutcome;
use App\Models\Payment;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;
use PaymentGatewayErrorType;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MidtransGateway implements PaymentGatewayInterface, PaymentWebhookInterface
{
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

        $orderItems = $payment->order->orderItems;

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->gateway_order_id,
                'gross_amount' => $payment->amount
            ],

            'item_details' => $orderItems->map(fn($item) => [
                'id' => $item->product_sku,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product_name,
            ])->values()->all(),

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

        try {
            $response = Snap::createTransaction($payload);
        } catch (\Exception $e) {
            $errorType = $this->classifyException($e);

            return [
                'success' => false,
                'status_code' => $e->getCode() ?: null,
                'error_type' => $errorType,
                'error_messages' => $this->extractApiErrorMessages($e),
            ];
        }

        if (is_object($response)) {
            $response = (array) $response;
        }

        if (! empty($response['error_messages'])) {
        }

        return [
            'success' => true,
            'redirect_url' => $response['redirect_url'],
            'token' => $response['token'],
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
            Log::warning('Payment gateway request sent', [
                'order_id' => $orderId,
                'statusCode' => $statusCode,
            ]);

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

    /**
     * Klasifikasi Exception dari ApiRequestor::remoteCall berdasarkan pattern message,
     * karena getCode() saja tidak cukup diandalkan (curl_errno, 0, atau HTTP status
     * bisa nyampur tergantung di titik mana exception dilempar).
     */
    private function classifyException(\Exception $e): PaymentGatewayErrorType
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        if (str_contains($message, 'ServerKey/ClientKey')) {
            return PaymentGatewayErrorType::CONFIGURATION;
        }

        if (str_contains($message, 'CURL Error')) {
            return PaymentGatewayErrorType::NETWORK;
        }

        if (str_contains($message, 'unable to json_decode')) {
            return PaymentGatewayErrorType::MALFORMED_RESPONSE;
        }

        if ($code >= 500) {
            return PaymentGatewayErrorType::SERVER_ERROR;
        }

        return PaymentGatewayErrorType::CLIENT_ERROR;
    }

    /**
     * Parse pesan Exception dari ApiRequestor buat ambil error_messages/validation_messages
     * asli dari body response Midtrans, kalau ada.
     */
    private function extractApiErrorMessages(\Exception $e): array
    {
        $message = $e->getMessage();

        if (preg_match('/API response:\s*(\{.*\})/s', $message, $matches)) {
            $decoded = json_decode($matches[1], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return (array) ($decoded['error_messages'] ?? $decoded['validation_messages'] ?? [$message]);
            }
        }

        return [$message];
    }
}
