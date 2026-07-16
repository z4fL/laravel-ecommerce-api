<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->firstOrCreate();

        $cart->load('cartItems.product');

        return $this->success(
            new CartResource($cart)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->user()->cart?->cartItems()->delete();

        return $this->deleted('Cart');
    }
}
