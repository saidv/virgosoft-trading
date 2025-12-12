<?php

namespace App\Http\Controllers;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Requests\GetOrderBookRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Repositories\Contracts\OrderRepository;
use App\Services\AssetService;
use App\Services\BalanceService;
use App\Services\CommissionService;
use App\Services\OrderMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Constructor
     *
     * @param  OrderRepository  $orderRepository  Order repository
     * @param  BalanceService  $balanceService  Balance service
     * @param  AssetService  $assetService  Asset service
     * @param  CommissionService  $commissionService  Commission service
     * @param  OrderMatchingService  $orderMatchingService  Order matching service
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private BalanceService $balanceService,
        private AssetService $assetService,
        private CommissionService $commissionService,
        private OrderMatchingService $orderMatchingService
    ) {}

    /**
     * Display a listing of the resource.
     * Fetches the public order book for a given symbol.
     */
    public function index(GetOrderBookRequest $request): JsonResponse
    {
        $symbol = $request->validated()['symbol'];

        $buyOrders = $this->orderRepository->getOpenBuyOrders($symbol);
        $sellOrders = $this->orderRepository->getOpenSellOrders($symbol);

        // Calculate best bid (highest buy order price)
        $bestBid = $buyOrders->isNotEmpty()
            ? $buyOrders->max('price')
            : null;

        // Calculate best ask (lowest sell order price)
        $bestAsk = $sellOrders->isNotEmpty()
            ? $sellOrders->min('price')
            : null;

        // Calculate spread
        $spread = $bestBid !== null && $bestAsk !== null
            ? bcsub($bestAsk, $bestBid, 8)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'symbol' => $symbol,
                'buy_orders' => $buyOrders,
                'sell_orders' => $sellOrders,
                'best_bid' => $bestBid,
                'best_ask' => $bestAsk,
                'spread' => $spread,
            ],
            'message' => 'Order book retrieved successfully.',
        ]);
    }

    /**
     * Fetches all orders belonging to the authenticated user.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        $orders = $this->orderRepository->getUserOrders($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'User orders retrieved successfully.',
        ]);
    }

    /**
     * Place a new order
     * POST /api/orders
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $side = OrderSide::from($request->side);
        $symbol = $request->symbol;
        $price = $request->price;
        $amount = $request->amount;

        // Calculate volume and commission
        $volume = bcmul($price, $amount, 8);
        $commission = $this->commissionService->calculate($volume);
        $totalWithCommission = bcadd($volume, $commission, 8);

        try {
            $order = DB::transaction(function () use ($user, $side, $symbol, $price, $amount, $totalWithCommission) {
                // Lock user row for update
                $user = $user->fresh();

                $this->validateAndLockFunds($user, $side, $symbol, $amount, $totalWithCommission);

                // Create the order via repository
                return $this->orderRepository->create([
                    'user_id' => $user->id,
                    'symbol' => $symbol,
                    'side' => $side,
                    'price' => $price,
                    'amount' => $amount,
                    'status' => OrderStatus::OPEN,
                ]);
            });

            // Try to match the order
            $trade = $this->orderMatchingService->matchOrder($order);

            // Refresh order status
            $order->refresh();

            return $this->buildOrderResponse($order, $trade, $commission);

        } catch (\Exception $e) {
            Log::error('Order placement failed', [
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => $side->value,
                'price' => $price,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate and lock funds/assets for the order
     */
    private function validateAndLockFunds($user, OrderSide $side, string $symbol, string $amount, string $totalWithCommission): void
    {
        if ($side === OrderSide::BUY) {
            // Check and lock USD balance
            if (! $this->balanceService->hasSufficientBalance($user, $totalWithCommission)) {
                throw new \Exception("Insufficient balance. Required: \${$totalWithCommission}, Available: \${$user->balance}");
            }

            // Deduct balance (including commission)
            $this->balanceService->lockForOrder($user, $totalWithCommission);
        } else {
            // Check and lock asset
            if (! $this->assetService->hasSufficientAsset($user, $symbol, $amount)) {
                $asset = $user->getAsset($symbol);
                $available = $asset ? $asset->getAvailableAmount() : '0';
                throw new \Exception("Insufficient {$symbol}. Required: {$amount}, Available: {$available}");
            }

            // Lock asset
            $this->assetService->lock($user, $symbol, $amount);
        }
    }

    /**
     * Build the order response
     */
    private function buildOrderResponse($order, $trade, $commission): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side->value,
                    'price' => $order->price,
                    'amount' => $order->amount,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'total_value' => $order->getTotalValue(),
                    'commission' => $commission,
                    'created_at' => $order->created_at,
                ],
                'matched' => $trade !== null,
            ],
            'message' => $trade ? 'Order placed and matched successfully' : 'Order placed successfully',
        ];

        if ($trade) {
            $response['data']['trade'] = [
                'id' => $trade->id,
                'price' => $trade->price,
                'amount' => $trade->amount,
                'commission' => $trade->commission,
            ];
        }

        return response()->json($response, 201);
    }

    /**
     * Cancel an order
     * POST /api/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($user, $id) {
                // Find and lock the order
                $order = $this->orderRepository->findByIdAndUserForUpdate($id, $user);

                if (! $order) {
                    throw new \Exception('Order not found');
                }

                if (! $order->status->isOpen()) {
                    throw new \Exception('Order cannot be cancelled (not open)');
                }

                // Refund based on order side
                if ($order->side === OrderSide::BUY) {
                    // Refund locked balance
                    $volume = bcmul($order->price, $order->amount, 8);
                    $commission = $this->commissionService->calculate($volume);
                    $totalRefund = bcadd($volume, $commission, 8);
                    $this->balanceService->releaseForOrder($user, $totalRefund);
                } else {
                    // Unlock asset
                    $this->assetService->unlock($user, $order->symbol, $order->amount);
                }

                // Mark as cancelled
                $this->orderRepository->markAsCancelled($order);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'symbol' => $order->symbol,
                        'side' => $order->side->value,
                        'price' => $order->price,
                        'amount' => $order->amount,
                        'status' => $order->status->value,
                        'status_label' => $order->status->label(),
                    ],
                ],
                'message' => 'Order cancelled successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'order_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
