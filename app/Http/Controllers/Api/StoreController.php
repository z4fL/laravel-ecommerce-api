<?php

namespace App\Http\Controllers\Api;

use App\Enum\StoreStatus;
use App\Enum\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserStoreRequest $request)
    {

        $user = auth('api')->user();

        if ($user->store()->exists()) {
            return $this->error('user already have store!', 409);
        }

        $store = DB::transaction(function () use ($request, $user) {
            $store = Store::create([
                'user_id' => $user->id,
                'status' => StoreStatus::ACTIVE,
                ...$request->safe()
            ]);

            $user->update([
                'role' => UserRole::SELLER
            ]);

            return $store;
        });

        return $this->created(
            'Store',
            new StoreResource(
                $store->load([
                    'user',
                    'products'
                ])
            )
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
