<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\TradeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    /**
     * Constructor
     *
     * @param  TradeRepository  $tradeRepository  Trade repository
     */
    public function __construct(
        private TradeRepository $tradeRepository
    ) {}

    /**
     * Get authenticated user's trade history
     * GET /api/trades
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $trades = $this->tradeRepository->getByUser($user);

        $trades->getCollection()->transform(function ($trade) use ($user) {
            $isBuyer = $trade->buyer_id === $user->id;

            return [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'side' => $isBuyer ? 'buy' : 'sell',
                'price' => $trade->price,
                'amount' => $trade->amount,
                'total' => $trade->getTotalValue(),
                'commission' => $trade->commission,
                'net_amount' => $isBuyer
                    ? bcadd($trade->getTotalValue(), $trade->commission, 8)
                    : $trade->getSellerReceives(),
                'created_at' => $trade->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $trades,
        ]);
    }
}
