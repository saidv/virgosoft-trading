<?php

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepository
{
    /**
     * Find order by ID
     */
    public function findById(int $id): ?Order;

    /**
     * Find order by ID with lock for update
     */
    public function findByIdForUpdate(int $id): ?Order;

    /**
     * Find user's order by ID
     */
    public function findByIdAndUser(int $id, User $user): ?Order;

    /**
     * Find user's order by ID with lock for update
     */
    public function findByIdAndUserForUpdate(int $id, User $user): ?Order;

    /**
     * Get open orders for orderbook (buy orders)
     */
    public function getOpenBuyOrders(string $symbol, int $limit = 20): Collection;

    /**
     * Get open orders for orderbook (sell orders)
     */
    public function getOpenSellOrders(string $symbol, int $limit = 20): Collection;

    /**
     * Get user orders with optional filters
     */
    public function getUserOrders(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find matching order for a buy order (excludes same user to prevent self-matching)
     */
    public function findMatchingOrderForBuy(string $symbol, string $price, string $amount, int $excludeUserId): ?Order;

    /**
     * Find matching order for a sell order (excludes same user to prevent self-matching)
     */
    public function findMatchingOrderForSell(string $symbol, string $price, string $amount, int $excludeUserId): ?Order;

    /**
     * Create a new order
     */
    public function create(array $data): Order;

    /**
     * Update order status
     */
    public function updateStatus(Order $order, OrderStatus $status): bool;

    /**
     * Mark order as filled
     */
    public function markAsFilled(Order $order): bool;

    /**
     * Mark order as cancelled
     */
    public function markAsCancelled(Order $order): bool;
}
