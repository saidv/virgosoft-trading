<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TradeRepository
{
    /**
     * Find trade by ID
     */
    public function findById(int $id): ?Trade;

    /**
     * Get trades by user (as buyer or seller)
     */
    public function getByUser(User $user, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get trades by symbol
     */
    public function getBySymbol(string $symbol, int $limit = 50): Collection;

    /**
     * Get recent trades
     */
    public function getRecent(int $limit = 50): Collection;

    /**
     * Create a new trade
     */
    public function create(array $data): Trade;

    /**
     * Create trade from matched orders
     *
     * @param  Order  $buyOrder  The buy order
     * @param  Order  $sellOrder  The sell order
     * @param  string  $executionPrice  The price at which the trade executes (maker's price)
     * @param  string  $commission  The commission amount
     */
    public function createFromOrders(Order $buyOrder, Order $sellOrder, string $executionPrice, string $commission): Trade;
}
