<?php

namespace Database\Seeders;

use App\Enums\Side;
use App\Enums\Status;
use App\Enums\Symbol;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the first test user
        $user1 = User::factory()->create([
            'name' => 'User1',
            'email' => 'u1@example.com',
            'password' => Hash::make('1234'),
            'balance' => 100000.00,
        ]);

        // Create the second test user
        $user2 = User::factory()->create([
            'name' => 'User 2',
            'email' => 'u2@example.com',
            'password' => Hash::make('1234'),
            'balance' => 100000.00,
        ]);

        $users = [$user1, $user2];

        foreach ($users as $user) {
            // Give each user some initial assets
            Asset::create([
                'user_id' => $user->id,
                'symbol_id' => Symbol::BTC,
                'amount' => 10.0,
                'locked_amount' => 0,
            ]);

            Asset::create([
                'user_id' => $user->id,
                'symbol_id' => Symbol::ETH,
                'amount' => 100.0,
                'locked_amount' => 0,
            ]);
        }

        // Seed some initial orders between them to populate the book
        // foreach (Symbol::cases() as $symbol) {
        //     // User 1 places some Buy orders
        //     Order::factory()->create([
        //         'user_id' => $user1->id,
        //         'symbol_id' => $symbol,
        //         'side_id' => Side::Buy,
        //         'price' => $symbol === Symbol::BTC ? 94000 : 2400,
        //         'amount' => 0.1,
        //         'status_id' => Status::Open,
        //     ]);

        //     // User 2 places some Sell orders
        //     Order::factory()->create([
        //         'user_id' => $user2->id,
        //         'symbol_id' => $symbol,
        //         'side_id' => Side::Sell,
        //         'price' => $symbol === Symbol::BTC ? 96000 : 2600,
        //         'amount' => 0.1,
        //         'status_id' => Status::Open,
        //     ]);
        // }
    }
}
