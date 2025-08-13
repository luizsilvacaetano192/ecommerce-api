<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Operações relacionadas aos usuários"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Lista todos os usuários",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="created_at", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    
    public function index()
    {
        try {
            $users = User::select('id', 'name', 'email', 'created_at')->paginate(10);
            return response()->json($users);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro ao listar usuários'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Cria um novo usuário",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Luiz silva caetano jr"),
     *             @OA\Property(property="email", type="string", example="luizsilvacaetano192@email.com"),
     *             @OA\Property(property="password", type="string", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="created_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */

    public function store(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);
            return response()->json($user, 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro ao criar usuário'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Mostra detalhes de um usuário",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do usuário",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="created_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */

    public function show($id)
    {
        try {
            $user = User::select('id','name','email','created_at')->find($id);
            if (!$user) return response()->json(['error' => 'Usuário não encontrado'], 404);
            return response()->json($user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro ao buscar usuário'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Atualiza um usuário",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Luiz caetano"),
     *             @OA\Property(property="email", type="string", example="luizcaetano182@email.com"),
     *             @OA\Property(property="password", type="string", example="novaSenha123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuário atualizado"),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) return response()->json(['error'=>'Usuário não encontrado'], 404);
            $data = $request->validated();
            if(isset($data['password'])) $data['password'] = bcrypt($data['password']);
            $user->update($data);
            Cache::forget("user:{$id}");
            return response()->json($user);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar usuário'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Remove um usuário",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Usuário deletado"),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */

    public function destroy(int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) return response()->json(['error'=>'Usuário não encontrado'], 404);
            $user->delete();
            Cache::forget("user:{$id}");
            return response()->json(['message'=>'Usuário deletado com sucesso']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error'=>'Erro ao deletar usuário'], 500);
        }
    }
}
