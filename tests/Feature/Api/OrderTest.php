<?php

use App\Enums\Side;
use App\Enums\Status;
use App\Enums\Symbol as SymbolEnum;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\User;
use Database\Seeders\SideSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\SymbolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SideSeeder::class);
    $this->seed(StatusSeeder::class);
    $this->seed(SymbolSeeder::class);
});

test('authenticated user can view profile', function () {
    $user = User::factory()->create(['balance' => 1000]);
    $symbol = Symbol::where('name', 'BTC')->first();
    Asset::create([
        'user_id' => $user->id,
        'symbol_id' => $symbol->id,
        'amount' => 1.5,
        'locked_amount' => 0.5,
    ]);

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJson([
            'balance' => '1000.00',
            'assets' => [
                [
                    'symbol' => 'BTC',
                    'amount' => '1.50000000',
                    'locked_amount' => '0.50000000',
                ],
            ],
        ]);
});

test('user can place a buy order', function () {
    $user = User::factory()->create(['balance' => 100000]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Buy->value,
        'price' => 95000,
        'amount' => 0.01,
    ]);

    $response->assertStatus(201);

    // 95000 * 0.01 = 950.
    // Fee = 950 * 0.015 = 14.25.
    // Total = 964.25
    $this->assertEquals(100000 - 964.25, $user->fresh()->balance);
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'side_id' => Side::Buy->value,
        'price' => 95000,
        'amount' => 0.01,
        'status_id' => Status::Open->value,
    ]);
});

test('user can place a sell order', function () {
    $user = User::factory()->create();
    $symbol = Symbol::where('name', 'BTC')->first();
    Asset::create([
        'user_id' => $user->id,
        'symbol_id' => $symbol->id,
        'amount' => 1.0,
        'locked_amount' => 0.0,
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Sell->value,
        'price' => 96000,
        'amount' => 0.1,
    ]);

    $response->assertStatus(201);

    $asset = Asset::where('user_id', $user->id)->where('symbol_id', $symbol->id)->first();
    $this->assertEquals(0.9, $asset->amount);
    $this->assertEquals(0.1, $asset->locked_amount);
});

test('orders can match', function () {
    $buyer = User::factory()->create(['balance' => 100000]);
    $seller = User::factory()->create(['balance' => 0]);
    $symbol = Symbol::where('name', 'BTC')->first();

    // Seller places sell order first
    Asset::create([
        'user_id' => $seller->id,
        'symbol_id' => $symbol->id,
        'amount' => 0.1,
        'locked_amount' => 0.0,
    ]);

    $this->actingAs($seller)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Sell->value,
        'price' => 95000,
        'amount' => 0.01,
    ]);

    // Buyer places buy order that matches
    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Buy->value,
        'price' => 95000,
        'amount' => 0.01,
    ]);

    $response->assertStatus(201);

    // Buyer balance: 100000 - (950 + 14.25) = 99035.75
    $this->assertEquals(99035.75, $buyer->fresh()->balance);

    // Seller balance: 0 + 950 = 950
    $this->assertEquals(950, $seller->fresh()->balance);

    // Buyer asset: 0.01
    $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol_id', $symbol->id)->first();
    $this->assertEquals(0.01, $buyerAsset->amount);

    // Seller asset: 0.1 - 0.01 = 0.09
    $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol_id', $symbol->id)->first();
    $this->assertEquals(0.09, $sellerAsset->amount);
    $this->assertEquals(0, $sellerAsset->locked_amount);

    // Both orders should be filled
    $this->assertDatabaseHas('orders', ['id' => 1, 'status_id' => Status::Filled->value]);
    $this->assertDatabaseHas('orders', ['id' => 2, 'status_id' => Status::Filled->value]);

    // Trade should be recorded
    $this->assertDatabaseHas('trades', [
        'buyer_order_id' => 2,
        'seller_order_id' => 1,
        'price' => 95000,
        'amount' => 0.01,
    ]);
});

test('buyer gets refund if matched at better price', function () {
    $seller = User::factory()->create(['balance' => 0]);
    $buyer = User::factory()->create(['balance' => 100000]);
    $symbol = Symbol::where('name', 'BTC')->first();

    // Seller limit price: 90000
    Asset::create([
        'user_id' => $seller->id,
        'symbol_id' => $symbol->id,
        'amount' => 0.1,
        'locked_amount' => 0.0,
    ]);
    $this->actingAs($seller)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Sell->value,
        'price' => 90000,
        'amount' => 0.01,
    ]);

    // Buyer limit price: 95000
    // Locked: 950 + (950 * 0.015) = 950 + 14.25 = 964.25
    // Matched at: 90000
    // Actual cost: 900 + (900 * 0.015) = 900 + 13.5 = 913.5
    // Refund: 964.25 - 913.5 = 50.75
    // Final balance: 100000 - 913.5 = 99086.5

    $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Buy->value,
        'price' => 95000,
        'amount' => 0.01,
    ]);

    $this->assertEquals(99086.5, $buyer->fresh()->balance);
});

test('user can cancel an open order', function () {
    $user = User::factory()->create(['balance' => 100000]);

    $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => SymbolEnum::BTC->value,
        'side' => Side::Buy->value,
        'price' => 95000,
        'amount' => 0.01,
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders/1/cancel');

    $response->assertStatus(200);
    $this->assertEquals(100000, $user->fresh()->balance);
    $this->assertDatabaseHas('orders', ['id' => 1, 'status_id' => Status::Cancelled->value]);
});

test('user can view open orders for a symbol', function () {
    $user = User::factory()->create();
    $symbol = Symbol::where('name', 'BTC')->first();

    Order::factory()->create([
        'user_id' => $user->id,
        'symbol_id' => $symbol->id,
        'side_id' => Side::Buy,
        'price' => 95000,
        'amount' => 0.01,
        'status_id' => Status::Open,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'symbol_id' => $symbol->id,
        'side_id' => Side::Sell,
        'price' => 96000,
        'amount' => 0.01,
        'status_id' => Status::Open,
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'buy')
        ->assertJsonCount(1, 'sell')
        ->assertJsonPath('buy.0.price', '95000.00')
        ->assertJsonPath('sell.0.price', '96000.00');
});
