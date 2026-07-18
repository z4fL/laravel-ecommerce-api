<?php

namespace App\Models;

use App\Enum\AddressLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'recipient_name',
    'phone',
    'label',
    'province',
    'city',
    'district',
    'postal_code',
    'address',
    'is_default',
])]
class ShippingAddress extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'label' => AddressLabel::class,
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
