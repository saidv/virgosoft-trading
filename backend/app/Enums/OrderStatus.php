<?php

namespace App\Enums;

enum OrderStatus: int
{
    case OPEN = 1;
    case FILLED = 2;
    case CANCELLED = 3;

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
            self::OPEN => 'Open',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if order is open
     */
    public function isOpen(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Check if order is filled
     */
    public function isFilled(): bool
    {
        return $this === self::FILLED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Get CSS color class
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::OPEN => 'text-blue-600',
            self::FILLED => 'text-green-600',
            self::CANCELLED => 'text-gray-500',
        };
    }
}
