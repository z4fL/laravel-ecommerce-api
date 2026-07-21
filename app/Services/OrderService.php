<?php

namespace App\Services;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly CartValidationService $cartValidationService
    ) {}

    public function create(User $user, ShippingAddress $shippingAddress): Order
    {
        $cart = $user->cart()->with('cartItems.product')->firstOrFail();
        $validatedCart = $this->cartValidationService->validate($cart);

        if (!$validatedCart['valid']) {
            throw ValidationException::withMessages([
                'cart' => $validatedCart['errors']
            ]);
        }

        $order = DB::transaction(function () use ($user, $cart, $shippingAddress, $validatedCart) {
            $total = $validatedCart['summary']['subtotal'];

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING_PAYMENT,

                'recipient_name' => $shippingAddress->recipient_name,
                'phone' => $shippingAddress->phone,
                'province' => $shippingAddress->province,
                'city' => $shippingAddress->city,
                'district' => $shippingAddress->district,
                'postal_code' => $shippingAddress->postal_code,
                'address' => $shippingAddress->address,

                'subtotal' => $validatedCart['summary']['subtotal'],
                'total' => $total,
            ]);

            $orderItemsData = collect($validatedCart['items'])->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'product_sku' => $item['product_sku'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ];
            })->all();

            $order->orderItems()->createMany($orderItemsData);

            $cart->cartItems()->delete();

            return $order;
        });

        return $order;
    }

    private function generateOrderNumber(): string
    {
        return sprintf(
            'ORD-%s-%s',
            now()->format('Ymd'),
            Str::upper(Str::random(6))
        );
    }
}
