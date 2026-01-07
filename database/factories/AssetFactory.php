<?php

namespace Database\Factories;

use App\Enums\Symbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

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
            'amount' => $this->faker->randomFloat(8, 0, 10),
            'locked_amount' => 0,
        ];
    }
}
