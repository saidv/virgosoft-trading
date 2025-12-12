<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
    ];

    /**
     * Order belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Trades associated with this order (as buy order)
     */
    public function buyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    /**
     * Trades associated with this order (as sell order)
     */
    public function sellTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    /**
     * Check if order is open
     */
    public function isOpen(): bool
    {
        return $this->status === OrderStatus::OPEN;
    }

    /**
     * Check if order is a buy order
     */
    public function isBuy(): bool
    {
        return $this->side === OrderSide::BUY;
    }

    /**
     * Check if order is a sell order
     */
    public function isSell(): bool
    {
        return $this->side === OrderSide::SELL;
    }

    /**
     * Calculate total value (price * amount)
     */
    public function getTotalValue(): string
    {
        return bcmul($this->price, $this->amount, 8);
    }

    /**
     * Scope for open orders
     */
    public function scopeOpen($query)
    {
        return $query->where('status', OrderStatus::OPEN);
    }

    /**
     * Scope for buy orders
     */
    public function scopeBuy($query)
    {
        return $query->where('side', OrderSide::BUY);
    }

    /**
     * Scope for sell orders
     */
    public function scopeSell($query)
    {
        return $query->where('side', OrderSide::SELL);
    }

    /**
     * Scope for orders by symbol
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'status' => OrderStatus::class,
            'price' => 'decimal:8',
            'amount' => 'decimal:8',
        ];
    }
}
