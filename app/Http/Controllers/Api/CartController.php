<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;

class CartController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = auth('api')->user();

        if (!$user->cart()->exists()) {
            $cart = new Cart();
            $cart->setRelation('cartItems', collect());
        }

        $cart = $user->cart->load('cartItems');

        return $this->success(new CartResource($cart->load([
            'cartItems',
        ])));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $user = auth('api')->user();

        $userCart = $user->cart;
        $userCart->cartItems()
            ->where('cart_id', $userCart->id)
            ->delete();

        return $this->deleted('Cart');
    }
}
