<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class DecreaseProductStock extends Command
{
    protected $signature = 'inventory:decrease {product} {quantity}';

    protected $description = 'Decrease a product stock quantity. (for testing concurrency only)';

    public function handle(InventoryService $inventoryService): int
    {
        $product = Product::query()->findOrFail($this->argument('product'));

        try {
            $inventoryService->decreaseStock(
                $product,
                (int) $this->argument('quantity'),
            );
        } catch (ValidationException $exception) {
            $this->error('insufficient_stock');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
