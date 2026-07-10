<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'seller_id',
    'category_id',
    'sku',
    'name',
    'slug',
    'description',
    'price',
    'status',
    'stock'
])]
class Product extends Model
{
    use HasUniqueSlug;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reorderImages(): void
    {
        $this->images()
            ->orderBy('sort_order')
            ->get()
            ->each(function ($image, $index) {
                $image->update([
                    'sort_order' => $index + 1,
                ]);
            });
    }

    #[Scope]
    protected function search(Builder $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }
        $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%")
                ->orWhereHas('category', function (Builder $q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('tags', function (Builder $q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('seller', function (Builder $q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
        });
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $category = $filters['category'] ?? null;
        $tag = $filters['tag'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;

        if ($category) {
            $query->whereHas('category', function (Builder $q) use ($category) {
                $q->where('slug', $category);
            });
        }

        if ($tag) {
            $query->whereHas('tags', function (Builder $q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        if (array_key_exists('in_stock', $filters)) {
            if ($filters['in_stock']) {
                $query->where('stock', '>', 0);
            } else {
                $query->where('stock', 0);
            }
        }
    }

    #[Scope]
    protected function sort(Builder $query, ?string $sort): void
    {
        if ($sort === null) {
            $query->latest();
            return;
        }

        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);
    }
}
