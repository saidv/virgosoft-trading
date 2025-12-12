<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile with balance and assets
     * GET /api/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('assets');

        $assets = $user->assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'symbol' => $asset->symbol,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
                'available_amount' => $asset->getAvailableAmount(),
                'created_at' => $asset->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
                'formatted_balance' => '$'.number_format((float) $user->balance, 2),
                'assets' => $assets,
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
