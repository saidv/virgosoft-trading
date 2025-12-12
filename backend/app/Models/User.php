<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * User's assets (BTC, ETH, etc.)
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * User's orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Trades where user is the buyer
     */
    public function buyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    /**
     * Trades where user is the seller
     */
    public function sellTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }

    /**
     * Get available balance (total balance)
     */
    public function getAvailableBalance(): string
    {
        return $this->balance;
    }

    /**
     * Get asset by symbol
     */
    public function getAsset(string $symbol): ?Asset
    {
        return $this->assets()->where('symbol', $symbol)->first();
    }

    /**
     * Get or create asset by symbol
     */
    public function getOrCreateAsset(string $symbol): Asset
    {
        return $this->assets()->firstOrCreate(
            ['symbol' => $symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:8',
        ];
    }
}
