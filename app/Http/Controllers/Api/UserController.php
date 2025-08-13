<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    public function index()
    {
        $page = request('page', 1);
        $cacheKey = "users_page_{$page}";

        $users = Cache::store('redis')->remember($cacheKey, 60, function () {
            return User::select('id', 'name', 'email', 'created_at')
                ->paginate(10);
        });

        return response()->json($users);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $user = User::create($data);

        Redis::connection()->flushdb();

        return response()->json($user, 201);
    }

    public function show(int $id)
    {
        $cacheKey = "user_{$id}";

        $user = Cache::store('redis')->remember($cacheKey, 60, function () use ($id) {
            return User::select('id', 'name', 'email', 'created_at')->findOrFail($id);
        });

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
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
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        Redis::connection()->flushdb();

        return response()->json(['message' => 'Usuário excluído com sucesso']);
    }
}
