<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Order $order)
    {
        $payment = $this->paymentService->create($order);

        return $this->created('Payment', new PaymentResource($payment));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
