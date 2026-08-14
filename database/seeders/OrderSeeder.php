<?php

namespace Database\Seeders;

use App\Enum\OrderStatus;
use App\Enum\ProductStatus;
use App\Enum\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::where('role', UserRole::CUSTOMER)->firstOrFail();

        $products = Product::query()
            ->where('status', ProductStatus::PUBLISHED)
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($products->count() < 2) {
            $this->command?->warn('OrderSeeder skipped: not enough published products.');
            return;
        }

        $subtotal =
            ($products[0]->price * 1) +
            ($products[1]->price * 2);

        $order = Order::updateOrCreate(
            ['order_number' => 'ORD-SANDBOX-001'],
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::PENDING_PAYMENT,

                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'postal_code' => '12110',
                'address' => 'Jl. Sandbox No. 1',

                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]
        );

        $order->orderItems()->delete();

        $order->orderItems()->createMany([
            [
                'product_id' => $products[0]->id,
                'product_sku' => $products[0]->sku,
                'product_name' => $products[0]->name,
                'price' => $products[0]->price,
                'quantity' => 1,
                'subtotal' => $products[0]->price,
            ],
            [
                'product_id' => $products[1]->id,
                'product_sku' => $products[1]->sku,
                'product_name' => $products[1]->name,
                'price' => $products[1]->price,
                'quantity' => 2,
                'subtotal' => $products[1]->price * 2,
            ],
        ]);
    }
}
