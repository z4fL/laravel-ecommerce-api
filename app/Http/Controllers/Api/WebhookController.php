<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\PaymentWebhookRequest;
use App\Services\PaymentEventProcessor;
use App\Services\PaymentWebhookGatewayResolver;

class WebhookController extends Controller
{
    public function handle(
        PaymentWebhookRequest $request,
        PaymentWebhookGatewayResolver $resolver,
        PaymentEventProcessor $processor
    ) {
        $gateway = $request->route('gateway');

        $webhookGateway = $resolver->resolve($gateway);

        $webhookGateway->verify($request);

        $result = $processor->process(
            $webhookGateway->normalize($request)
        );

        return $this->success([
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
        ]);
    }
}
