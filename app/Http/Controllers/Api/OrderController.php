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

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Lista os pedidos do usuário autenticado",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de pedidos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="value", type="number"),
     *                     @OA\Property(property="currency", type="string"),
     *                     @OA\Property(property="created_at", type="string"),
     *                     @OA\Property(property="updated_at", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Cria um novo pedido",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"value","currency","description"},
     *             @OA\Property(property="description", type="string", example="Pedido de teste"),
     *             @OA\Property(property="value", type="number", example=150.50),
     *             @OA\Property(property="currency", type="string", example="BRL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pedido criado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="created_at", type="string"),
     *             @OA\Property(property="updated_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erro de validação"),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */

    public function store(StoreOrderRequest $request)
    {
        try {
            $data = $request->validated();


            $data['user_id'] = auth()->id();

            $order = Order::create($data);

            return response()->json($order, 201);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao criar pedido.', 500, $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Exibe um pedido específico",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do pedido",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do pedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="converted_value", type="number"),
     *             @OA\Property(property="created_at", type="string"),
     *             @OA\Property(property="updated_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Pedido não encontrado"),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */

    public function show(int $id)
    {
        try {
            $order = Order::with('user')->find($id);
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

    /**
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     summary="Atualiza um pedido existente",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do pedido",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", example="Pedido de teste"),
     *             @OA\Property(property="value", type="number", example=200.00),
     *             @OA\Property(property="currency", type="string", example="USD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedido atualizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="created_at", type="string"),
     *             @OA\Property(property="updated_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Pedido não encontrado"),
     *     @OA\Response(response=422, description="Erro de validação"),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */

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

    /**
     * @OA\Delete(
     *     path="/api/orders/{id}",
     *     summary="Exclui um pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do pedido",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Pedido excluído com sucesso"),
     *     @OA\Response(response=404, description="Pedido não encontrado"),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */

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
