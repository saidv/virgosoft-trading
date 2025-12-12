<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    /**
     * Asset belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get available amount (total - locked)
     */
    public function getAvailableAmount(): string
    {
        return bcsub($this->amount, $this->locked_amount, 8);
    }

    /**
     * Check if asset has sufficient available amount
     */
    public function hasSufficientAmount(string $required): bool
    {
        return bccomp($this->getAvailableAmount(), $required, 8) >= 0;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'locked_amount' => 'decimal:8',
        ];
    }
}
