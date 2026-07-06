<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    use HasUniqueSlug;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
