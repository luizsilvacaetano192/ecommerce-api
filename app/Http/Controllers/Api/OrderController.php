<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    protected CurrencyConverterService $currencyConverter;

    public function __construct(CurrencyConverterService $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function index()
    {
        $cacheKey = 'orders_page_' . request('page', 1);

        $orders = Cache::remember($cacheKey, 60, function () {
            return Order::with('user')->paginate(10);
        });

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = Order::create($request->validated());

        return response()->json($order, 201);
    }

    public function show(int $id)
    {
        $order = Cache::remember("order_{$id}", 60, function () use ($id) {
            return Order::with('user')->findOrFail($id);
        });

        $order->converted_amount = $this->currencyConverter->convert(
            $order->amount,
            $order->currency
        );

        return response()->json($order);
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->validated());

        Cache::forget("order_{$id}");

        return response()->json($order);
    }

    public function destroy(int $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        Cache::forget("order_{$id}");

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
