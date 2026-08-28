<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function checkAvailability(Product $product, int $quantity): bool
    {
        $this->validateQuantity($quantity);

        return $product->stock >= $quantity;
    }

    public function decreaseStock(Product $product, int $quantity): void
    {
        $this->validateQuantity($quantity);

        $lockedProduct = Product::query()
            ->lockForUpdate()
            ->findOrFail($product->getKey());

        if ($lockedProduct->stock < $quantity) {
            throw ValidationException::withMessages([
                'stock' => 'Insufficient product stock.',
            ]);
        }

        $lockedProduct->decrement('stock', $quantity);
    }

    public function increaseStock(Product $product, int $quantity): void
    {
        $this->validateQuantity($quantity);

        $lockedProduct = Product::query()
            ->lockForUpdate()
            ->findOrFail($product->getKey());

        $lockedProduct->increment('stock', $quantity);
    }

    public function setStock(Product $product, int $stock): void
    {
        $this->validateStock($stock);

        DB::transaction(function () use ($product, $stock) {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->getKey());

            $lockedProduct->update([
                'stock' => $stock,
            ]);
        });
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }
    }

    private function validateStock(int $stock): void
    {
        if ($stock < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Stock cannot be negative.',
            ]);
        }
    }
}
