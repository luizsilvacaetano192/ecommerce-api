<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class OrderController extends Controller
{
    protected CurrencyConverterService $currencyConverter;

    public function __construct(CurrencyConverterService $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function index()
    {
        try {
            $cacheKey = 'orders_page_' . request('page', 1);

            $orders = Cache::remember($cacheKey, 60, function () {
                return Order::with('user')->paginate(10);
            });

            return response()->json($orders);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao listar pedidos.', 500, $e);
        }
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = Order::create($request->validated());

            return response()->json($order, 201);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao criar pedido.', 500, $e);
        }
    }

    public function show(int $id)
    {
        try {
            $order = Cache::remember("order_{$id}", 60, function () use ($id) {
                return Order::with('user')->findOrFail($id);
            });

            $order->converted_amount = $this->currencyConverter->convert(
                $order->amount,
                $order->currency
            );

            return response()->json($order);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Pedido não encontrado.', 404);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao buscar pedido.', 500, $e);
        }
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->update($request->validated());

            Cache::forget("order_{$id}");

            return response()->json($order);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Pedido não encontrado.', 404);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar pedido.', 500, $e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            Cache::forget("order_{$id}");

            return response()->json(['message' => 'Pedido excluído com sucesso']);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Pedido não encontrado.', 404);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao excluir pedido.', 500, $e);
        }
    }

    protected function errorResponse(string $message, int $statusCode = 400, $details = null)
    {
        $response = ['error' => $message];

        if ($details) {
            if ($details instanceof Exception) {
                $response['details'] = $details->getMessage();
            } else {
                $response['details'] = $details;
            }
        }

        return response()->json($response, $statusCode);
    }
}
