<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            Log::info('Login attempt', ['email' => $request->email]);
            
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Invalid credentials', ['email' => $request->email]);
                return $this->errorResponse('Credenciais inválidas.', 422, ['email' => ['Credenciais inválidas.']]);
            }

            $token = $user->createToken('api-token')->plainTextToken;
            
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
            
        } catch (Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erro ao tentar realizar login.', 500, $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $tokenId = $user->currentAccessToken()->id;
            
            $user->currentAccessToken()->delete();
            
            Log::info('User logged out', [
                'user_id' => $user->id,
                'token_id' => $tokenId
            ]);

            return response()->json(['message' => 'Logout realizado com sucesso.']);
            
        } catch (Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Erro ao tentar realizar logout.', 500, $e->getMessage());
        }
    }

    public function me(Request $request)
    {
        try {
            Log::debug('User data requested', [
                'user_id' => $request->user()->id
            ]);
            
            return response()->json($request->user());
            
        } catch (Exception $e) {
            Log::error('Failed to fetch user data', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return $this->errorResponse('Erro ao recuperar dados do usuário.', 500, $e->getMessage());
        }
    }

    protected function errorResponse(string $message, int $statusCode = 400, $details = null)
    {
        $response = ['error' => $message];

        if ($details) {
            $response['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }
}