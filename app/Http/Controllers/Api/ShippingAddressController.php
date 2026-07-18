<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShippingAddressRequest;
use App\Http\Requests\UpdateShippingAddressRequest;
use App\Http\Resources\ShippingAddressResource;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ShippingAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->success(ShippingAddressResource::collection(
            $request->user()
                ->addresses()
                ->get()
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShippingAddressRequest $request)
    {
        Gate::authorize('create', ShippingAddress::class);

        $address = DB::transaction(function () use ($request) {
            $user = $request->user();
            $hasAddress = $user->addresses()->exists();
            $data = $request->validated();

            if (!$hasAddress) {
                $data['is_default'] = true;
            } else if ($request->boolean('is_default')) {
                $user->addresses()
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $data['is_default'] = true;
            } else {
                $data['is_default'] = false;
            }

            $address = $user->addresses()->create($data);

            return $address;
        });

        return $this->created('Shipping Address', new ShippingAddressResource($address));
    }

    /**
     * Display the specified resource.
     */
    public function show(ShippingAddress $shipping_address)
    {
        Gate::authorize('view', $shipping_address);
        return $this->success(new ShippingAddressResource($shipping_address));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShippingAddressRequest $request, ShippingAddress $shipping_address)
    {
        Gate::authorize('update', $shipping_address);

        $data = $request->validated();
        $shipping_address->update($data);

        return $this->updated('Shipping Address', new ShippingAddressResource($shipping_address));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ShippingAddress $shipping_address)
    {
        Gate::authorize('delete', $shipping_address);
        $user = $request->user();

        DB::transaction(function () use ($user, $shipping_address) {
            $isDefault = $shipping_address->is_default;
            $shipping_address->delete();

            if ($isDefault) {
                $nextDefault = $user->addresses()->oldest()->first();

                $nextDefault?->update([
                    'is_default' => true,
                ]);
            }
        });

        return $this->deleted('Shipping Address');
    }

    public function makeDefault(Request $request, ShippingAddress $shipping_address)
    {
        Gate::authorize('update', $shipping_address);

        $user = $request->user();
        $isDefault = $shipping_address->is_default;

        if ($isDefault) {
            return $this->success(
                message: "Shipping address aldready default.",
                status: 204
            );
        }

        $data = DB::transaction(function () use ($user, $shipping_address) {
            $user->addresses()->update(['is_default' => false]);

            $shipping_address->update(['is_default' => true]);

            return $shipping_address;
        });

        return $this->success($data, "Successfully change default Shipping address.");
    }
}
