<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentWebhookInterface
{
    public function verify(Request $request): void;
    public function normalize(Request $request): array;
}
