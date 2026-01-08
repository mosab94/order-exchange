<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_order_id' => Order::factory(),
            'seller_order_id' => Order::factory(),
            'price' => $this->faker->randomFloat(2, 30000, 100000),
            'amount' => $this->faker->randomFloat(8, 0.001, 1),
        ];
    }
}
