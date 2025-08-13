<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;

class UserControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Cache::shouldReceive('forget')
            ->andReturn(true);

        Cache::shouldReceive('flush')
            ->andReturn(true);
    }

    public function testIndexReturnsPaginatedUsersWithAuthentication()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        User::factory()->count(15)->create();

        $response = $this->getJson('/api/users');

        if ($response->status() === 500) {
            dd($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at']
                ],
                'links'
            ]);
    }

    public function testStoreCreatesUser()
    {
        $payload = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/users', $payload);

        if ($response->status() !== 201) {
            dd($response->json()); // Debug the error
        }

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New User']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function testShowReturnsUser()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}");

        // Debug if test fails
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email
            ]);
    }

    public function testShowReturns404IfNotFound()
    {
        $response = $this->getJson('/api/users/999999');

        // Debug if test fails
        if ($response->status() !== 404) {
            dump($response->json());
        }

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Usuário não encontrado'
            ]);
    }

    public function testUpdateModifiesUser()
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ];

        $response = $this->putJson("/api/users/{$user->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name'])
            ->assertJsonStructure([
                'id', 'name', 'email'
            ]);

        $this->assertDatabaseHas('users', ['name' => 'Updated Name']);
    }

    public function testUpdateReturns404IfNotFound()
    {
        $payload = [
            'name' => 'Any Name',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->putJson('/api/users/999', $payload);

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Usuário não encontrado']);
    }

    public function testDestroyDeletesUser()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Usuário deletado com sucesso']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testDestroyReturns404IfNotFound()
    {
        $response = $this->deleteJson('/api/users/999');

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Usuário não encontrado']);
    }
}
