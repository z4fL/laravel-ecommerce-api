<?php

namespace App\Contracts;

use App\Enum\PaymentOutcome;
use Illuminate\Http\Request;

interface PaymentWebhookInterface
{
    public function verify(Request $request): void;

    public function normalize(Request $request): array;

    public function determineOutcome(string $status): PaymentOutcome;
}
