<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\Contracts\TradeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentTradeRepository implements TradeRepository
{
    /**
     * Constructor
     *
     * @param  Trade  $model  The Trade model
     */
    public function __construct(
        private Trade $model
    ) {}

    /**
     * Find trade by ID
     *
     * @param  int  $id  The trade ID
     * @return Trade|null The found trade or null if not found
     */
    public function findById(int $id): ?Trade
    {
        return $this->model->find($id);
    }

    /**
     * Get trades by user (as buyer or seller)
     *
     * @param  User  $user  The user
     * @param  int  $perPage  The number of trades per page
     * @return LengthAwarePaginator The paginated list of trades
     */
    public function getByUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get trades by symbol
     *
     * @param  string  $symbol  The trading symbol
     * @param  int  $limit  The maximum number of trades to retrieve
     * @return Collection The collection of trades
     */
    public function getBySymbol(string $symbol, int $limit = 50): Collection
    {
        return $this->model
            ->where('symbol', $symbol)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent trades
     *
     * @param  int  $limit  The maximum number of recent trades to retrieve
     * @return Collection The collection of recent trades
     */
    public function getRecent(int $limit = 50): Collection
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new trade
     *
     * @param  array  $data  The trade data
     * @return Trade The created trade
     */
    public function create(array $data): Trade
    {
        return $this->model->create($data);
    }

    /**
     * Create trade from matched orders
     *
     * @param  Order  $buyOrder  The buy order
     * @param  Order  $sellOrder  The sell order
     * @param  string  $executionPrice  The price at which the trade executes (maker's price)
     * @param  string  $commission  The commission amount
     * @return Trade The created trade
     */
    public function createFromOrders(Order $buyOrder, Order $sellOrder, string $executionPrice, string $commission): Trade
    {
        return $this->create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
            'symbol' => $buyOrder->symbol,
            'price' => $executionPrice, // Trade executes at maker's price
            'amount' => $buyOrder->amount,
            'commission' => $commission,
        ]);
    }
}
