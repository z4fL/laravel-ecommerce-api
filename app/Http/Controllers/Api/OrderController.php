<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\ShippingAddress;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{

    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(OrderIndexRequest $request)
    {
        $user = $request->user('api');

        $perPage = $request->integer('per_page', 10);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->withCount('orderItems')
            ->latest()
            ->paginate($perPage);

        return $this->pagination(
            paginator: $orders,
            data: OrderResource::collection($orders)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $user = $request->user('api');
        $shippingAddress = ShippingAddress::findOrFail($request->validated('shipping_address_id'));

        $order = $this->orderService->create(
            $user,
            $shippingAddress
        );

        return $this->created('Order', new OrderResource($order->loadCount('orderItems')));
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        return $this->success(
            new OrderResource(
                $order
                    ->load('orderItems')
                    ->loadCount('orderItems')
            )
        );
    }


    /**
     * Remove the specified resource from storage.
     */
    public function cancel(Order $order)
    {
        Gate::authorize('cancel', $order);

        $order = $this->orderService->cancel($order);

        return $this->success(new OrderResource(
            $order->load('orderItems')
                ->loadCount('orderItems')
        ));
    }
}
