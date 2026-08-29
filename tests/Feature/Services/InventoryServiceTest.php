<?php

namespace Tests\Feature\Services;

use App\Enum\InventoryHistoryType;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
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

    public function test_it_decreases_stock_and_creates_inventory_history(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create();

        $this->inventoryService->decreaseStock(
            $product,
            3,
            $order,
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);

        $this->assertDatabaseHas('inventory_histories', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'type' => InventoryHistoryType::DECREASE->value,
            'quantity' => 3,
            'stock_before' => 10,
            'stock_after' => 7,
        ]);
    }

    public function test_it_increases_stock_and_creates_inventory_history(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create();

        $this->inventoryService->increaseStock(
            $product,
            3,
            $order,
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 13,
        ]);

        $this->assertDatabaseHas('inventory_histories', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'type' => InventoryHistoryType::INCREASE->value,
            'quantity' => 3,
            'stock_before' => 10,
            'stock_after' => 13,
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

        $this->assertDatabaseCount('inventory_histories', 0);
    }

    public function test_it_rejects_invalid_decrease_quantity(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $this->expectException(ValidationException::class);

        $this->inventoryService->decreaseStock($product, 0);

        $this->assertDatabaseCount('inventory_histories', 0);
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

        $this->assertDatabaseCount('inventory_histories', 0);
    }

    public function test_it_rolls_back_stock_and_inventory_history_when_transaction_fails(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $order = Order::factory()->create();

        try {
            DB::transaction(function () use ($product, $order) {
                $this->inventoryService->decreaseStock(
                    $product,
                    3,
                    $order,
                );

                throw new RuntimeException('Force transaction rollback.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Force transaction rollback.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);

        $this->assertDatabaseCount('inventory_histories', 0);
    }

    public function test_concurrent_decrease_cannot_oversell_stock(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped(
                'The pcntl extension is required for the concurrency test.'
            );
        }

        /*
         * The concurrency test cannot run while RefreshDatabase keeps
         * the test inside an uncommitted transaction.
         *
         * Commit the fixture before creating child processes.
         */
        $this->commitTestingTransaction();

        $product = Product::factory()->create([
            'stock' => 1,
        ]);

        $productId = $product->getKey();

        DB::connection()->commit();

        $directory = storage_path('framework/testing/inventory');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $runId = uniqid('inventory-', true);

        $startFile = "{$directory}/{$runId}-start";
        $readyFiles = [
            "{$directory}/{$runId}-a-ready",
            "{$directory}/{$runId}-b-ready",
        ];

        $resultFiles = [
            "{$directory}/{$runId}-a-result.json",
            "{$directory}/{$runId}-b-result.json",
        ];

        $this->cleanupConcurrencyFiles([
            $startFile,
            ...$readyFiles,
            ...$resultFiles,
        ]);

        $children = [];

        foreach ($resultFiles as $index => $resultFile) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Unable to fork process for concurrency test.');
            }

            if ($pid === 0) {
                $readyFile = $readyFiles[$index];

                $this->runConcurrentDecrease(
                    $productId,
                    $readyFile,
                    $startFile,
                    $resultFile,
                );

                exit(0);
            }

            $children[] = $pid;
        }

        $this->waitForFiles($readyFiles);

        /*
         * Both processes have created their independent database
         * connections and are ready to execute the mutation.
         */
        touch($startFile);

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            $this->assertTrue(
                pcntl_wifexited($status),
                "Child process {$pid} did not exit normally."
            );

            $this->assertSame(
                0,
                pcntl_wexitstatus($status),
                "Child process {$pid} exited with an error."
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

        $this->assertDatabaseCount('inventory_histories', 1);

        $this->assertDatabaseHas('inventory_histories', [
            'product_id' => $productId,
            'order_id' => null,
            'type' => InventoryHistoryType::DECREASE->value,
            'quantity' => 1,
            'stock_before' => 1,
            'stock_after' => 0,
        ]);

        $this->cleanupConcurrencyFiles([
            $startFile,
            ...$readyFiles,
            ...$resultFiles,
        ]);
    }

    private function runConcurrentDecrease(
        int|string $productId,
        string $readyFile,
        string $startFile,
        string $resultFile,
    ): void {
        /*
         * Force a fresh database connection in the child process.
         */
        DB::purge();

        $service = app(InventoryService::class);

        touch($readyFile);

        $this->waitForFile($startFile);

        $result = [
            'success' => false,
            'exception' => null,
        ];

        try {
            $product = Product::query()->findOrFail($productId);

            $service->decreaseStock($product, 1);

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
    }

    private function waitForFiles(array $files, int $timeoutSeconds = 10): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        foreach ($files as $file) {
            while (! file_exists($file)) {
                if (microtime(true) >= $deadline) {
                    $this->fail(
                        "Timed out waiting for concurrency file: {$file}"
                    );
                }

                usleep(10_000);
            }
        }
    }

    private function waitForFile(string $file, int $timeoutSeconds = 10): void
    {
        $this->waitForFiles([$file], $timeoutSeconds);
    }

    private function cleanupConcurrencyFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function commitTestingTransaction(): void
    {
        $connection = DB::connection();

        if ($connection->transactionLevel() > 0) {
            $connection->commit();
        }
    }
}
