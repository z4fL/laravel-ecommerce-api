<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description'])]
class Category extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            $category->slug = self::generateUniqueSlug($category->name);
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('name')) {
                $category->slug = self::generateUniqueSlug(
                    $category->name,
                    $category->id
                );
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    private static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            self::query()
            ->where('slug', $slug)
            ->when(
                $ignoreId,
                fn($query) => $query->whereKeyNot($ignoreId)
            )
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
