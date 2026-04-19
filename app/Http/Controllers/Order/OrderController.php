<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 10), 100));
        $page = max(1, (int) $request->query('page', 1));
        $q = $request->query('q');
        return $this->orderService->getOrders($limit, $page, $q);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        return $this->orderService->createOrder($request->toDTO());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->orderService->getOrderById($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        return $this->orderService->updateOrder($order, $request->toDTO());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->orderService->deleteOrder($id);
    }
}
