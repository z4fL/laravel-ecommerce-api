<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CartItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCartItemRequest $request, Product $product)
    {
        $user = auth('api')->user();

        $validated = $request->validated();

        $product->loadMissing('store');

        if (! $product->is_active) {
            return $this->error(
                'Product is inactive.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($product->store->user_id === $user->id) {
            return $this->error(
                'You cannot add your own product to the cart.',
                Response::HTTP_FORBIDDEN
            );
        }

        $cartItem = DB::transaction(function () use ($validated, $user, $product) {

            $cart = $user->cart()->firstOrCreate([
                'user_id' => $user->id,
            ]);

            $cartItem = $cart->cartItems()
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $validated['quantity']);
            } else {
                $cartItem = $cart->cartItems()->create([
                    'product_id'     => $product->id,
                    'quantity'       => $validated['quantity'],
                    'price_snapshot' => $product->price,
                ]);
            }

            $cartItem->refresh();

            return $cartItem;
        });

        return $this->created(
            'Cart item',
            new CartItemResource(
                $cartItem->load([
                    'product',
                ])
            )
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartItemRequest $request, CartItem $item)
    {
        $item->loadMissing('cart');

        Gate::authorize('update', $item);

        $validated = $request->validated();

        $item->update($validated);
        $item->refresh()->load('product');

        return $this->updated(
            'Cart item',
            new CartItemResource(
                $item->load([
                    'product',
                ])
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $item)
    {
        $item->loadMissing('cart');

        Gate::authorize('delete', $item);

        $item->delete();

        return $this->deleted('Cart item');
    }
}
