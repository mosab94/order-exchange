<?php

namespace App\Http\Controllers\Api;

use App\Enums\Side;
use App\Enums\Status;
use App\Enums\Symbol as SymbolEnum;
use App\Events\OrderbookUpdated;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Symbol;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class OrderController extends Controller
{
    public function __construct(protected MatchingService $matchingService) {}

    /**
     * Returns authenticated user's orders.
     */
    public function history(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['symbol', 'status', 'side'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Returns all open orders for orderbook (buy & sell).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string',
        ]);

        $symbolName = $request->query('symbol');
        $symbol = Symbol::where('name', $symbolName)->firstOrFail();

        $orders = Order::where('symbol_id', $symbol->id)
            ->where('status_id', Status::Open)
            ->orderBy('price', 'desc')
            ->get()
            ->groupBy(fn ($order) => strtolower($order->side_id->name));

        return response()->json([
            'buy' => $orders->get('buy', []),
            'sell' => $orders->get('sell', []),
        ]);
    }

    /**
     * Creates a limit order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', Rule::in(array_column(SymbolEnum::cases(), 'name'))],
            'side' => ['required', 'string', Rule::in(array_column(Side::cases(), 'name'))],
            'price' => 'required|numeric|gt:0',
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = $request->user();
        $symbolEnum = constant(SymbolEnum::class . '::' . $validated['symbol']);
        $side = constant(Side::class . '::' . $validated['side']);
        $price = $validated['price'];
        $amount = $validated['amount'];
        $commissionRate = 0.015;

        return DB::transaction(function () use ($user, $symbolEnum, $side, $price, $amount, $commissionRate) {
            if ($side === Side::Buy) {
                $totalCost = $price * $amount;
                $fee = $totalCost * $commissionRate;
                $totalWithFee = $totalCost + $fee;

                if ($user->balance < $totalWithFee) {
                    return response()->json(['message' => 'Insufficient USD balance'], 422);
                }

                $user->decrement('balance', $totalWithFee);
            } else {
                $asset = Asset::firstOrCreate([
                    'user_id' => $user->id,
                    'symbol_id' => $symbolEnum->value,
                ], [
                    'amount' => 0,
                    'locked_amount' => 0,
                ]);

                if ($asset->amount < $amount) {
                    return response()->json(['message' => 'Insufficient asset balance'], 422);
                }

                $asset->decrement('amount', $amount);
                $asset->increment('locked_amount', $amount);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'symbol_id' => $symbolEnum->value,
                'side_id' => $side->value,
                'price' => $price,
                'amount' => $amount,
                'status_id' => Status::Open->value,
            ]);

            OrderbookUpdated::dispatch($symbolEnum);

            $this->matchingService->match($order);

            return response()->json($order->fresh(), 201);
        });
    }

    /**
     * Cancels an open order and releases locked USD or assets.
     */
    public function cancel(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status_id !== Status::Open) {
            return response()->json(['message' => 'Order is not open'], 422);
        }

        DB::transaction(function () use ($order) {
            $order->update(['status_id' => Status::Cancelled->value]);

            if ($order->side_id === Side::Buy) {
                $totalCost = $order->price * $order->amount;
                $fee = $totalCost * 0.015;
                $order->user->increment('balance', $totalCost + $fee);
            } else {
                $asset = Asset::where('user_id', $order->user_id)
                    ->where('symbol_id', $order->symbol_id)
                    ->first();

                $asset->decrement('locked_amount', $order->amount);
                $asset->increment('amount', $order->amount);
            }

            OrderbookUpdated::dispatch($order->symbol_id);
        });

        return response()->json(['message' => 'Order cancelled']);
    }
}
