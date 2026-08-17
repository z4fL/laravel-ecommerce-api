<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\PaymentWebhookRequest;
use App\Services\Payment\PaymentEventProcessor;
use App\Services\Payment\PaymentStatusService;
use App\Services\Payment\PaymentWebhookGatewayResolver;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(
        PaymentWebhookRequest $request,
        PaymentWebhookGatewayResolver $resolver,
        PaymentEventProcessor $processor,
        PaymentStatusService $statusService,
    ) {
        $gateway = $request->route('gateway');

        $webhookGateway = $resolver->resolve($gateway);

        Log::info('Payment webhook received', [
            'gateway' => $gateway,
            'gateway_order_id' => $request['order_id'] ?? null,
            'gateway_transaction_id' => $request['transaction_id'] ?? null,
            'status' => $request['transaction_status'] ?? null,
        ]);

        $webhookGateway->verify($request);

        $normalized = $webhookGateway->normalize($request);

        $result = $processor->process($normalized, $webhookGateway);

        Log::info('Payment event processed', [
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
        ]);

        $transition = $statusService->update($result);

        return $this->success([
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
            'transition' => $transition->value,
        ]);
    }
}
