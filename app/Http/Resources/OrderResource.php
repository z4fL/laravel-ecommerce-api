<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'items_count' => $this->whenCounted('orderItems'),
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
