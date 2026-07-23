<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = [
            'transaction_details' => [
                'order_id' => fake()->uuid(),
                'gross_amount' => 100_000,
            ],
            'customer_details' => [
                'first_name' => 'Sandbox',
                'email' => 'sandbox@example.com',
                'phone' => '08123456789',
            ],
            'item_details' => [
                [
                    'id' => 1,
                    'price' => 100000,
                    'quantity' => 1,
                    'name' => 'Testing Product',
                ],
            ],
        ];

        $response = $this->paymentGateway->createTransaction($payload);

        return $this->success($response);
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
