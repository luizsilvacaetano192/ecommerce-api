<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function testLoginReturnsTokenWithValidCredentials(): void
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

    public function testLoginFailsWithInvalidCredentials(): void
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

    public function testMeReturnsAuthenticatedUser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/me');

        $response->assertOk()
                 ->assertJson([
                     'id' => $user->id,
                     'email' => $user->email,
                 ]);
    }

    public function testLogoutRevokesToken(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertOk()
                 ->assertJson(['message' => 'Logout realizado com sucesso.']);
    }
}
