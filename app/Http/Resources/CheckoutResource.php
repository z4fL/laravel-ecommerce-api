<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "shipping_address" => ShippingAddressResource::make(
                 $this['shipping_address']
            ),
            "summary" => $this['summary'],
            "items" => CheckoutItemResource::collection($this['items'])
        ];
    }
}
