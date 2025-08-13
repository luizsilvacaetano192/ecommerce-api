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
use Illuminate\Support\Facades\Log;

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
            $cacheKey = 'orders_user_' . auth()->id() . '_page_' . request('page', 1);

            $orders = Cache::remember($cacheKey, 60, function () {
                return Order::where('user_id', auth()->id())
                    ->paginate(10);
            });

            return $orders;
        } catch (\Exception $e) {
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

            $order =  Order::with('user')->find($id);
           
            if (!$order) {
                return $this->errorResponse('Pedido não encontrado.', 404);
            }

            $order->converted_value = $this->currencyConverter->convert(
                $order->value,
                $order->currency
            );

            return $order;

        } catch (Exception $e) {
            Log::error("OrderController show error - ID: {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erro ao buscar pedido.', 500);
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
