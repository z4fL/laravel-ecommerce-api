<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,

            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'payment_method' => $this->payment_method,
            'status' => $this->status,

            'amount' => $this->amount,

            'payment_url' => $this->payment_url,
            'expired_at' => $this->expired_at,
            'paid_at' => $this->paid_at,

            'snap_token' => data_get($this->metadata, 'snap_token')
            // 'metadata' => $this->metadata,
        ];
    }
}
