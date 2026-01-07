<?php

namespace Database\Factories;

use App\Enums\Side;
use App\Enums\Status;
use App\Enums\Symbol;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol_id' => Symbol::BTC,
            'side_id' => Side::Buy,
            'price' => $this->faker->randomFloat(2, 30000, 100000),
            'amount' => $this->faker->randomFloat(8, 0.001, 1),
            'status_id' => Status::Open,
        ];
    }
}
