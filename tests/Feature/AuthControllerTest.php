<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $password = 'senha123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $payload = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertOk()
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                 ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('senha123'),
        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'senhaErrada',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(422)
                 ->assertJson(['error' => 'Credenciais inválidas.']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/me');

        $response->assertOk()
                 ->assertJson([
                     'id' => $user->id,
                     'email' => $user->email,
                 ]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
                         ->postJson('/api/logout');

        $response->assertOk()
                 ->assertJson(['message' => 'Logout realizado com sucesso.']);
    }
}
