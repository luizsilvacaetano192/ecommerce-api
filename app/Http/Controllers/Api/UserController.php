<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class UserController extends Controller
{
    public function index()
    {
        try {
            $page = request('page', 1);
            $cacheKey = "users_page_{$page}";

            $users = Cache::store('redis')->remember($cacheKey, 60, function () {
                return User::select('id', 'name', 'email', 'created_at')
                    ->paginate(10);
            });

            return response()->json($users);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao listar usuários.', 500, $e);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $data = $request->validated();
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            $user = User::create($data);

            Redis::connection()->flushdb();

            return response()->json($user, 201);

        } catch (ValidationException $e) {
            return $this->errorResponse('Dados inválidos.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao criar usuário.', 500, $e);
        }
    }

    public function show(int $id)
    {
        try {
            $cacheKey = "user_{$id}";

            $user = Cache::store('redis')->remember($cacheKey, 60, function () use ($id) {
                return User::select('id', 'name', 'email', 'created_at')->findOrFail($id);
            });

            return response()->json($user);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Usuário não encontrado.', 404);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao buscar usuário.', 500, $e);
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validated();

            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            Redis::connection()->flushdb();

            return response()->json($user);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Usuário não encontrado.', 404);
        } catch (ValidationException $e) {
            return $this->errorResponse('Dados inválidos.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar usuário.', 500, $e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            Redis::connection()->flushdb();

            return response()->json(['message' => 'Usuário excluído com sucesso']);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Usuário não encontrado.', 404);
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao excluir usuário.', 500, $e);
        }
    }

    protected function errorResponse(string $message, int $statusCode = 400, $details = null)
    {
        $response = ['error' => $message];

        if ($details) {
            $response['details'] = is_string($details) ? $details : $details;
        }

        return response()->json($response, $statusCode);
    }
}
