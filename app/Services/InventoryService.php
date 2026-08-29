<?php

namespace App\Services;

use App\Enum\InventoryHistoryType;
use App\Models\InventoryHistory;
use App\Models\Order;
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

    public function decreaseStock(Product $product, int $quantity, ?Order $order = null): void
    {
        $this->validateQuantity($quantity);

        $this->mutateStock(
            product: $product,
            quantity: $quantity,
            type: InventoryHistoryType::DECREASE,
            order: $order,
        );
    }

    public function increaseStock(Product $product, int $quantity, ?Order $order = null): void
    {
        $this->validateQuantity($quantity);

        $this->mutateStock(
            product: $product,
            quantity: $quantity,
            type: InventoryHistoryType::INCREASE,
            order: $order,
        );
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

    private function mutateStock(
        Product $product,
        int $quantity,
        InventoryHistoryType $type,
        ?Order $order = null,
    ): void {
        $lockedProduct = Product::query()
            ->lockForUpdate()
            ->findOrFail($product->getKey());

        $stockBefore = $lockedProduct->stock;

        if (
            $type === InventoryHistoryType::DECREASE
            && $stockBefore < $quantity
        ) {
            throw ValidationException::withMessages([
                'stock' => 'Insufficient product stock.',
            ]);
        }

        $stockAfter = match ($type) {
            InventoryHistoryType::DECREASE => $stockBefore - $quantity,
            InventoryHistoryType::INCREASE => $stockBefore + $quantity,
        };

        $lockedProduct->update([
            'stock' => $stockAfter,
        ]);

        InventoryHistory::create([
            'product_id' => $lockedProduct->getKey(),
            'order_id' => $order?->getKey(),
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
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
