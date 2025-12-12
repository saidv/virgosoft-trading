<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Events\OrderMatched;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TradeRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderMatchingService
{
    /**
     * Constructor
     *
     * @param  OrderRepository  $orderRepository  Order repository
     * @param  TradeRepository  $tradeRepository  Trade repository
     * @param  UserRepository  $userRepository  User repository
     * @param  BalanceService  $balanceService  Balance service
     * @param  AssetService  $assetService  Asset service
     * @param  CommissionService  $commissionService  Commission service
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private TradeRepository $tradeRepository,
        private UserRepository $userRepository,
        private BalanceService $balanceService,
        private AssetService $assetService,
        private CommissionService $commissionService
    ) {}

    /**
     * Try to match a newly created order
     * FULL MATCH ONLY: Orders must have the same amount to match
     *
     * @param  Order  $order  The new order to match
     * @return Trade|null The executed trade or null if no match found
     */
    public function matchOrder(Order $order): ?Trade
    {
        return DB::transaction(function () use ($order) {
            // Refresh and lock the order
            $order = $this->orderRepository->findByIdForUpdate($order->id);

            if (! $order || ! $order->isOpen()) {
                return null;
            }

            // Find matching order (FULL MATCH ONLY - same amount)
            $matchingOrder = $this->findMatchingOrder($order);

            if (! $matchingOrder) {
                Log::info('No matching order found', ['order_id' => $order->id]);

                return null;
            }

            Log::info('Found matching order', [
                'new_order_id' => $order->id,
                'matching_order_id' => $matchingOrder->id,
            ]);

            // Execute the trade
            return $this->executeTrade($order, $matchingOrder);
        });
    }

    /**
     * Find a matching order from the opposite side
     * FULL MATCH ONLY: Must have same amount
     * Excludes orders from the same user to prevent self-matching
     *
     * @param  Order  $order  The new order
     * @return Order|null The found matching order or null if not found
     */
    private function findMatchingOrder(Order $order): ?Order
    {
        if ($order->side === OrderSide::BUY) {
            return $this->orderRepository->findMatchingOrderForBuy(
                $order->symbol,
                $order->price,
                $order->amount,
                $order->user_id
            );
        }

        return $this->orderRepository->findMatchingOrderForSell(
            $order->symbol,
            $order->price,
            $order->amount,
            $order->user_id
        );
    }

    /**
     * Execute trade between two matching orders
     *
     * @param  Order  $newOrder  The newly created order
     * @param  Order  $matchingOrder  The found matching order
     * @return Trade The executed trade
     */
    private function executeTrade(Order $newOrder, Order $matchingOrder): Trade
    {
        // Determine which is buy and which is sell
        $buyOrder = $newOrder->side === OrderSide::BUY ? $newOrder : $matchingOrder;
        $sellOrder = $newOrder->side === OrderSide::SELL ? $newOrder : $matchingOrder;

        // Lock users for update
        $buyer = $this->userRepository->findByIdForUpdate($buyOrder->user_id);
        $seller = $this->userRepository->findByIdForUpdate($sellOrder->user_id);

        // Use the maker's (matching order's) price for execution
        $executionPrice = $matchingOrder->price;
        $amount = $newOrder->amount; // Full match

        // Calculate trade volume and the commission (paid by the buyer/taker)
        $volume = bcmul($executionPrice, $amount, 8);
        $commission = $this->commissionService->calculate($volume);

        Log::info('Executing trade', [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'price' => $executionPrice,
            'amount' => $amount,
            'volume' => $volume,
            'commission' => $commission,
        ]);

        $this->processBuyerFunds($buyer, $buyOrder, $volume, $commission);
        $this->processSellerFunds($seller, $volume);
        $this->processAssetTransfer($seller, $buyer, $newOrder->symbol, $amount);

        // Mark orders as filled
        $this->orderRepository->markAsFilled($buyOrder);
        $this->orderRepository->markAsFilled($sellOrder);

        // Create trade record with execution price
        $trade = $this->tradeRepository->createFromOrders($buyOrder, $sellOrder, $executionPrice, $commission);

        // Broadcast to both users
        $this->broadcastOrderMatched($trade, $buyer, $seller);

        Log::info('Trade executed successfully', [
            'trade_id' => $trade->id,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
        ]);

        return $trade;
    }

    /**
     * Process buyer funds (refund difference)
     */
    private function processBuyerFunds(User $buyer, Order $buyOrder, string $volume, string $commission): void
    {
        // 1. Calculate the original amount locked by the buyer when the order was placed
        $originalBuyOrderVolume = bcmul($buyOrder->price, $buyOrder->amount, 8);
        $originalCommission = $this->commissionService->calculate($originalBuyOrderVolume);
        $originalLockedAmount = bcadd($originalBuyOrderVolume, $originalCommission, 8);

        // 2. Calculate the final cost to the buyer (volume of the trade at execution price + commission)
        $finalCostWithCommission = bcadd($volume, $commission, 8);

        // 3. Calculate the refund due to the buyer (difference between what was locked and what was ultimately spent)
        $refundAmount = bcsub($originalLockedAmount, $finalCostWithCommission, 8);

        // 4. Credit the buyer with the refund amount, if any (e.g., due to price improvement)
        if (bccomp($refundAmount, '0', 8) > 0) {
            $this->balanceService->credit($buyer, $refundAmount);
            Log::info('Refunded buyer for price difference and/or lower commission', [
                'refund' => $refundAmount,
                'buyer_id' => $buyOrder->user_id,
            ]);
        }
    }

    /**
     * Process seller funds (credit volume)
     */
    private function processSellerFunds(User $seller, string $volume): void
    {
        // 5. The seller receives the full volume of the trade
        $this->balanceService->credit($seller, $volume);
    }

    /**
     * Process asset transfer from seller to buyer
     */
    private function processAssetTransfer(User $seller, User $buyer, string $symbol, string $amount): void
    {
        // 6. Transfer the asset from the seller's locked holdings to the buyer's available balance
        $this->assetService->transferLocked(
            $seller,
            $buyer,
            $symbol,
            $amount
        );
    }

    /**
     * Broadcast OrderMatched event via Pusher
     *
     * @param  Trade  $trade  The executed trade
     * @param  User  $buyer  The buyer user
     * @param  User  $seller  The seller user
     */
    private function broadcastOrderMatched(Trade $trade, User $buyer, User $seller): void
    {
        // Refresh users to get updated balances
        $buyer->refresh();
        $seller->refresh();

        // Broadcast to buyer
        broadcast(new OrderMatched($trade, $buyer->id))->toOthers();

        // Broadcast to seller
        broadcast(new OrderMatched($trade, $seller->id))->toOthers();
    }
}
