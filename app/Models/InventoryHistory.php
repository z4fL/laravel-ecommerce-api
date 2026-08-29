<?php

namespace App\Models;

use App\Enum\InventoryHistoryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'order_id',
    'type',
    'quantity',
    'stock_before',
    'stock_after',
])]
class InventoryHistory extends Model
{

    protected function casts(): array
    {
        return [
            'type' => InventoryHistoryType::class,
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withDefault();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
