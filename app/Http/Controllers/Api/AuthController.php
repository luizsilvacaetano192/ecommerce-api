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
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Faz login e retorna o token de acesso",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="luizsilvacaetano192@gmail.com"),
     *             @OA\Property(property="password", type="string", example="senhaSegura123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="1|abc123..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João da Silva"),
     *                 @OA\Property(property="email", type="string", example="user@email.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Credenciais inválidas"),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Faz logout e invalida o token atual",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Logout realizado com sucesso"),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Retorna os dados do usuário autenticado",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="João da Silva"),
     *             @OA\Property(property="email", type="string", example="user@email.com")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno")
     * )
     */
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
