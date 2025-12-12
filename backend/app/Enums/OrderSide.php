<?php

namespace App\Enums;

enum OrderSide: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::BUY => 'Buy',
            self::SELL => 'Sell',
        };
    }

    /**
     * Check if this is a buy order
     */
    public function isBuy(): bool
    {
        return $this === self::BUY;
    }

    /**
     * Check if this is a sell order
     */
    public function isSell(): bool
    {
        return $this === self::SELL;
    }

    /**
     * Get the opposite side
     */
    public function opposite(): self
    {
        return match ($this) {
            self::BUY => self::SELL,
            self::SELL => self::BUY,
        };
    }
}
