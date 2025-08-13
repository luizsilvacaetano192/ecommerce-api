<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id'     => User::factory(),
            'description' => $this->faker->sentence(),
            'value'       => $this->faker->randomFloat(2, 10, 1000),
            'currency'    => $this->faker->randomElement(['BRL', 'USD']),
        ];
    }
}
