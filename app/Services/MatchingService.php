<?php

namespace App\Services;

use App\Enums\Side;
use App\Enums\Status;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    /**
     * Matches new orders with the first valid counter order.
     */
    public function match(Order $order): void
    {
        if ($order->status_id !== Status::Open) {
            return;
        }

        $counterSide = $order->side_id === Side::Buy ? Side::Sell : Side::Buy;

        $query = Order::where('symbol_id', $order->symbol_id)
            ->where('side_id', $counterSide->value)
            ->where('status_id', Status::Open->value)
            ->where('amount', $order->amount); // Full match only

        if ($order->side_id === Side::Buy) {
            // New BUY -> match with first SELL where sell.price <= buy.price
            $query->where('price', '<=', $order->price)
                ->orderBy('price', 'asc'); // Best price for buyer
        } else {
            // New SELL -> match with first BUY where buy.price >= sell.price
            $query->where('price', '>=', $order->price)
                ->orderBy('price', 'desc'); // Best price for seller
        }

        $match = $query->first();

        if ($match) {
            $this->executeMatch($order, $match);
        }
    }

    /**
     * Executes the trade between two orders.
     */
    protected function executeMatch(Order $order, Order $match): void
    {
        DB::transaction(function () use ($order, $match) {
            $buyOrder = $order->side_id === Side::Buy ? $order : $match;
            $sellOrder = $order->side_id === Side::Sell ? $order : $match;

            $price = $match->price; // Use price from the order book
            $amount = $order->amount;
            $volume = $price * $amount;
            $commissionRate = 0.015;

            // Mark orders as filled
            $order->update(['status_id' => Status::Filled->value]);
            $match->update(['status_id' => Status::Filled->value]);

            // Create Trade record
            Trade::create([
                'buyer_order_id' => $buyOrder->id,
                'seller_order_id' => $sellOrder->id,
                'price' => $price,
                'amount' => $amount,
            ]);

            // Seller receives USD
            $sellOrder->user->increment('balance', $volume);

            // Buyer receives Assets
            $buyerAsset = Asset::firstOrCreate([
                'user_id' => $buyOrder->user_id,
                'symbol_id' => $buyOrder->symbol_id->value,
            ], ['amount' => 0, 'locked_amount' => 0]);

            $buyerAsset->increment('amount', $amount);

            // Release Seller's locked assets
            $sellerAsset = Asset::where('user_id', $sellOrder->user_id)
                ->where('symbol_id', $sellOrder->symbol_id->value)
                ->first();

            $sellerAsset->decrement('locked_amount', $amount);

            // Refund buyer if match price was lower than limit price (including fee refund)
            $lockedVolume = $buyOrder->price * $amount;
            $lockedFee = $lockedVolume * $commissionRate;
            $actualFee = $volume * $commissionRate;

            $totalLocked = $lockedVolume + $lockedFee;
            $totalActual = $volume + $actualFee;

            $refund = $totalLocked - $totalActual;
            if ($refund > 0) {
                $buyOrder->user->increment('balance', $refund);
            }
        });
    }
}
