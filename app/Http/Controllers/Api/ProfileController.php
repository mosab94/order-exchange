<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Returns authenticated user's USD balance + asset balances.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets()->with('symbol')->get()->map(fn ($asset) => [
                'symbol' => $asset->symbol->name,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
            ]),
        ]);
    }
}
