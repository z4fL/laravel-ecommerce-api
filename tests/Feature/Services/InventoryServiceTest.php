<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = app(InventoryService::class);
    }

    public function test_it_can_check_stock_availability(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->assertTrue(
            $this->inventoryService->checkAvailability($product, 10)
        );

        $this->assertTrue(
            $this->inventoryService->checkAvailability($product, 5)
        );

        $this->assertFalse(
            $this->inventoryService->checkAvailability($product, 11)
        );
    }

    public function test_it_rejects_invalid_quantity_when_checking_availability(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->expectException(ValidationException::class);

        $this->inventoryService->checkAvailability($product, 0);
    }

    public function test_it_decreases_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->inventoryService->decreaseStock($product, 3);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_it_rejects_decrease_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create([
            'stock' => 2,
        ]);

        try {
            $this->inventoryService->decreaseStock($product, 3);

            $this->fail('Expected insufficient stock validation exception.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Insufficient product stock.',
                $exception->errors()['stock'][0]
            );
        }

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_it_rejects_invalid_decrease_quantity(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->expectException(ValidationException::class);

        $this->inventoryService->decreaseStock($product, 0);
    }

    public function test_it_does_not_allow_stock_to_become_negative(): void
    {
        $product = Product::factory()->create([
            'stock' => 1,
        ]);

        try {
            $this->inventoryService->decreaseStock($product, 2);
        } catch (ValidationException) {
            // Expected.
        }

        $product->refresh();

        $this->assertGreaterThanOrEqual(0, $product->stock);
        $this->assertSame(1, $product->stock);
    }

    public function test_concurrent_decrease_cannot_oversell_stock(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped(
                'The pcntl extension is required for the concurrency test.'
            );
        }

        $product = Product::factory()->create([
            'stock' => 1,
        ]);

        $resultDirectory = storage_path('framework/testing/inventory');

        if (! is_dir($resultDirectory)) {
            mkdir($resultDirectory, 0777, true);
        }

        $runId = uniqid('inventory-', true);

        $resultFiles = [
            $resultDirectory . "/{$runId}-a.json",
            $resultDirectory . "/{$runId}-b.json",
        ];

        $children = [];

        foreach ($resultFiles as $resultFile) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Unable to fork process for concurrency test.');
            }

            if ($pid === 0) {
                $result = [
                    'success' => false,
                    'exception' => null,
                ];

                try {
                    $service = app(InventoryService::class);

                    $service->decreaseStock(
                        Product::query()->findOrFail($product->id),
                        1,
                    );

                    $result['success'] = true;
                } catch (\Throwable $exception) {
                    $result['exception'] = [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ];
                }

                file_put_contents(
                    $resultFile,
                    json_encode($result, JSON_THROW_ON_ERROR),
                    LOCK_EX,
                );

                exit(0);
            }

            $children[] = $pid;
        }

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            $this->assertTrue(
                pcntl_wifexited($status),
                "Child process {$pid} did not exit normally."
            );
        }

        $results = [];

        foreach ($resultFiles as $resultFile) {
            $this->assertFileExists($resultFile);

            $contents = file_get_contents($resultFile);

            $this->assertNotFalse($contents);

            $results[] = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            unlink($resultFile);
        }

        $successfulRequests = collect($results)
            ->where('success', true)
            ->count();

        $failedRequests = collect($results)
            ->where('success', false)
            ->count();

        $this->assertSame(
            1,
            $successfulRequests,
            'Exactly one concurrent request must successfully decrease the only available stock unit.'
        );

        $this->assertSame(
            1,
            $failedRequests,
            'Exactly one concurrent request must fail because the stock is exhausted.'
        );

        $product->refresh();

        $this->assertSame(0, $product->stock);

        $this->assertGreaterThanOrEqual(0, $product->stock);

        $failedResult = collect($results)
            ->firstWhere('success', false);

        $this->assertSame(
            ValidationException::class,
            $failedResult['exception']['class']
        );

        $this->assertSame(
            'Insufficient product stock.',
            $failedResult['exception']['message']
        );
    }
}
