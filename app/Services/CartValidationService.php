<?php

namespace App\Services;

use App\Enum\ProductStatus;
use App\Models\Cart;

class CartValidationService
{
    private function makeResult(): array
    {
        return [
            'valid' => true,
            'summary' => [
                'items_count' => 0,
                'subtotal' => 0,
            ],
            'items' => [],
            'errors' => [],
        ];
    }

    private function addError(
        array &$result,
        int $cartItemId,
        string $code,
        string $message
    ): void {
        $result['errors'][] = [
            'id' => $cartItemId,
            'code' => $code,
            'message' => $message,
        ];
    }

    public function validate(Cart $cart): array
    {
        $cart->loadMissing('cartItems.product');

        $cartItems = $cart->cartItems;

        if ($cartItems->isEmpty()) {
            $result['errors'][] = [
                'code' => 'EMPTY_CART',
                'message' => 'Cart is empty.',
            ];
            $result['valid'] = false;

            return $result;
        }

        $result = $this->makeResult();

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            if ($product->status === ProductStatus::DRAFT) {
                $this->addError(
                    $result,
                    $cartItem->id,
                    'PRODUCT_NOT_PUBLISHED',
                    'Requested product is not published.'
                );

                continue;
            }

            if ($product->stock < $cartItem->quantity) {
                $this->addError(
                    $result,
                    $cartItem->id,
                    'OUT_OF_STOCK',
                    'Requested quantity exceeds available stock.'
                );

                continue;
            }

            if ($product->price !== $cartItem->price_snapshot) {
                $this->addError(
                    $result,
                    $cartItem->id,
                    'PRICE_CHANGED',
                    'Product price has changed.'
                );

                continue;
            }


            $subtotal = $product->price * $cartItem->quantity;

            $result['items'][] = [
                'id' => $cartItem->id,
                'product_id' => $product->id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $product->price,
                'subtotal' => $subtotal,
            ];

            $result['summary']['items_count']++;

            $result['summary']['subtotal'] += $subtotal;
        }

        $result['valid'] = empty($result['errors']);

        return $result;
    }
}
