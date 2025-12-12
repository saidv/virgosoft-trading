<?php

namespace App\Repositories;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentOrderRepository implements OrderRepository
{
    /**
     * Constructor
     *
     * @param  Order  $model  The Order model
     */
    public function __construct(
        private Order $model
    ) {}

    /**
     * Find order by ID
     *
     * @param  int  $id  The order ID
     * @return Order|null The found order or null if not found
     */
    public function findById(int $id): ?Order
    {
        return $this->model->find($id);
    }

    /**
     * Find order by ID with lock for update
     *
     * @param  int  $id  The order ID
     * @return Order|null The found order or null if not found
     */
    public function findByIdForUpdate(int $id): ?Order
    {
        return $this->model->lockForUpdate()->find($id);
    }

    /**
     * Find user's order by ID
     *
     * @param  int  $id  The order ID
     * @param  User  $user  The user
     * @return Order|null The found order or null if not found
     */
    public function findByIdAndUser(int $id, User $user): ?Order
    {
        return $this->model
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Find user's order by ID with lock for update
     *
     * @param  int  $id  The order ID
     * @param  User  $user  The user
     * @return Order|null The found order or null if not found
     */
    public function findByIdAndUserForUpdate(int $id, User $user): ?Order
    {
        return $this->model
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Get open buy orders for orderbook (highest price first)
     *
     * @param  string  $symbol  The trading symbol
     * @param  int  $limit  The maximum number of orders to retrieve
     * @return Collection The collection of open buy orders
     */
    public function getOpenBuyOrders(string $symbol, int $limit = 20): Collection
    {
        return $this->model
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN)
            ->where('side', OrderSide::BUY)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get open sell orders for orderbook (lowest price first)
     *
     * @param  string  $symbol  The trading symbol
     * @param  int  $limit  The maximum number of orders to retrieve
     * @return Collection The collection of open sell orders
     */
    public function getOpenSellOrders(string $symbol, int $limit = 20): Collection
    {
        return $this->model
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN)
            ->where('side', OrderSide::SELL)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user orders with optional filters
     *
     * @param  User  $user  The user
     * @param  array  $filters  The filters (symbol, side, status)
     * @param  int  $perPage  The number of orders per page
     * @return LengthAwarePaginator The paginated list of user orders
     */
    public function getUserOrders(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if (isset($filters['symbol'])) {
            $query->where('symbol', $filters['symbol']);
        }

        if (isset($filters['side'])) {
            $query->where('side', $filters['side']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find matching order for a buy order (FULL MATCH ONLY)
     * Finds first SELL where sell.price <= buy.price AND sell.amount = buy.amount
     * Excludes orders from the same user to prevent self-matching
     *
     * @param  string  $symbol  The trading symbol
     * @param  string  $price  The buy order price
     * @param  string  $amount  The buy order amount
     * @param  int  $excludeUserId  The user ID to exclude (to prevent self-matching)
     * @return Order|null The found matching order or null if not found
     */
    public function findMatchingOrderForBuy(string $symbol, string $price, string $amount, int $excludeUserId): ?Order
    {
        return $this->model
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN)
            ->where('side', OrderSide::SELL)
            ->where('price', '<=', $price)
            ->where('amount', $amount) // FULL MATCH ONLY
            ->where('user_id', '!=', $excludeUserId) // Prevent self-matching
            ->orderBy('price', 'asc')  // Best price first (lowest)
            ->orderBy('created_at', 'asc') // FIFO for same price
            ->lockForUpdate()
            ->first();
    }

    /**
     * Find matching order for a sell order (FULL MATCH ONLY)
     * Finds first BUY where buy.price >= sell.price AND buy.amount = sell.amount
     * Excludes orders from the same user to prevent self-matching
     *
     * @param  string  $symbol  The trading symbol
     * @param  string  $price  The sell order price
     * @param  string  $amount  The sell order amount
     * @param  int  $excludeUserId  The user ID to exclude (to prevent self-matching)
     * @return Order|null The found matching order or null if not found
     */
    public function findMatchingOrderForSell(string $symbol, string $price, string $amount, int $excludeUserId): ?Order
    {
        return $this->model
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN)
            ->where('side', OrderSide::BUY)
            ->where('price', '>=', $price)
            ->where('amount', $amount) // FULL MATCH ONLY
            ->where('user_id', '!=', $excludeUserId) // Prevent self-matching
            ->orderBy('price', 'desc') // Best price first (highest)
            ->orderBy('created_at', 'asc') // FIFO for same price
            ->lockForUpdate()
            ->first();
    }

    /**
     * Create a new order
     *
     * @param  array  $data  The order data
     * @return Order The created order
     */
    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    /**
     * Update order status
     *
     * @param  Order  $order  The order to update
     * @param  OrderStatus  $status  The new status
     * @return bool True if update was successful, false otherwise
     */
    public function updateStatus(Order $order, OrderStatus $status): bool
    {
        return $order->update(['status' => $status]);
    }

    /**
     * Mark order as filled
     *
     * @param  Order  $order  The order to mark as filled
     * @return bool True if update was successful, false otherwise
     */
    public function markAsFilled(Order $order): bool
    {
        return $this->updateStatus($order, OrderStatus::FILLED);
    }

    /**
     * Mark order as cancelled
     *
     * @param  Order  $order  The order to mark as cancelled
     * @return bool True if update was successful, false otherwise
     */
    public function markAsCancelled(Order $order): bool
    {
        return $this->updateStatus($order, OrderStatus::CANCELLED);
    }
}
