<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;

class OrderControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $currencyConverterMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyConverterMock = Mockery::mock(\App\Services\CurrencyConverterService::class);
        $this->app->instance(\App\Services\CurrencyConverterService::class, $this->currencyConverterMock);
    }

    public function testIndexReturnsPaginatedOrders()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        Order::factory()->count(15)->create([
            'user_id' => $user->id,
            'description' => 'Pedido de teste',
            'value' => 100,
            'currency' => 'USD',
        ]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'user_id', 'description', 'value', 'currency', 'created_at', 'updated_at']
                     ],
                     'links'
                 ]);
    }

    public function testStoreCreatesOrder()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $payload = [
            'user_id' => $user->id,
            'description' => 'Pedido de teste',
            'value' => 100,
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['value' => 100]);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'value' => 100]);
    }

    public function testShowReturnsOrderWithConvertedValue()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'description' => 'Pedido de teste',
            'value' => 200,
            'currency' => 'USD',
        ]);

        $this->currencyConverterMock
             ->shouldReceive('convert')
             ->once()
             ->with(200, 'USD')
             ->andReturn(['USD' => 400]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
                         ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $order->id,
                     'converted_value' => ['USD' => 400],
                 ]);
    }

    public function testShowReturns404IfNotFound()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/orders/999');

        $response->assertStatus(404)
                 ->assertJsonFragment(['error' => 'Pedido não encontrado.']);
    }

    public function testUpdateModifiesOrder()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'description' => 'Pedido original',
            'value' => 100,
            'currency' => 'USD'
        ]);
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $payload = [
            'user_id' => $user->id,
            'description' => 'Pedido atualizado',
            'value' => 150,
            'currency' => 'USD',
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['value' => 150, 'currency' => 'USD']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'value' => 150, 'currency' => 'USD']);
    }

    public function testUpdateReturns404IfNotFound()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $payload = ['description' => 'Teste', 'value' => 200, 'currency' => 'USD'];

        $response = $this->putJson('/api/orders/999', $payload);

        $response->assertStatus(404)
                 ->assertJsonFragment(['error' => 'Pedido não encontrado.']);
    }

    public function testDestroyDeletesOrder()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'description' => 'Pedido para deletar',
            'value' => 100,
            'currency' => 'USD'
        ]);
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Pedido excluído com sucesso']);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function testDestroyReturns404IfNotFound()
    {
        $user = User::factory()->create();
        /** @var \App\Models\User $user */
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson('/api/orders/999');

        $response->assertStatus(404)
                 ->assertJsonFragment(['error' => 'Pedido não encontrado.']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
