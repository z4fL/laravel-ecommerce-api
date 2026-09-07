<?php

use App\Enum\InventoryHistoryType;
use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class)->group('inventory-concurrency');

it('serializes concurrent decreases without overselling or lost updates', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Concurrency coverage requires the shared PostgreSQL test database.');
    }

    $product = Product::factory()->create(['stock' => 1]);
    $processes = collect(range(1, 2))->map(function () use ($product) {
        $process = new Process([
            PHP_BINARY,
            base_path('artisan'),
            'inventory:decrease',
            (string) $product->id,
            '1',
            '--env=testing',
        ], base_path());

        $process->setTimeout(10);

        return $process;
    })->all();

    foreach ($processes as $process) {
        $process->start();
    }

    foreach ($processes as $process) {
        $process->wait();
    }

    $successful = collect($processes)->filter(fn (Process $process) => $process->isSuccessful());
    $failed = collect($processes)->reject(fn (Process $process) => $process->isSuccessful());

    expect($successful)->toHaveCount(1);
    expect($failed)->toHaveCount(1);

    expect($failed->first()->getOutput())->toContain('insufficient_stock');
    expect($product->fresh()->stock)->toBe(0);
    expect(InventoryHistory::query()->where('product_id', $product->id)->count())->toBe(1);
    expect(InventoryHistory::query()->sole()->type)->toBe(InventoryHistoryType::DECREASE);
    expect(InventoryHistory::query()->sole()->stock_before)->toBe(1);
    expect(InventoryHistory::query()->sole()->stock_after)->toBe(0);
});
