<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\PaymentWebhookRequest;
use App\Services\Payment\PaymentEventProcessor;
use App\Services\Payment\PaymentStatusService;
use App\Services\Payment\PaymentWebhookGatewayResolver;

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

        $webhookGateway->verify($request);

        $result = $processor->process(
            $webhookGateway->normalize($request)
        );

        $transition = $statusService->update($result);

        return $this->success([
            'payment_id' => $result->paymentId,
            'outcome' => $result->outcome->value,
            'transition' => $transition->value,
        ]);
    }
}
