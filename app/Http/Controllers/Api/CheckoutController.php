<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\CheckoutResource;
use App\Services\CartValidationService;

class CheckoutController extends Controller
{
    public function preview(
        CheckoutRequest $request,
        CartValidationService $cartValidationService
    ) {
        $user = $request->user();
        $cart = $user->cart()->firstOrCreate();

        $shippingAddress = $user->addresses()->findOrFail(
            $request->validated('shipping_address_id')
        );

        $validatedCart = $cartValidationService->validate($cart);

        if (!$validatedCart['valid']) {
            return $this->error(
                'Cart not valid',
                422,
                $validatedCart['errors']
            );
        }

        $checkout = [
            'shipping_address' => $shippingAddress,
            ...$validatedCart
        ];

        return $this->success(new CheckoutResource(
            $checkout
        ));
    }
}
