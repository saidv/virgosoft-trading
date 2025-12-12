<?php

namespace App\Services;

class CommissionService
{
    /**
     * Commission rate: 1.5%
     */
    private const COMMISSION_RATE = '0.015';

    /**
     * Calculate commission amount from volume
     *
     * @param  string  $volume  The USD volume of the trade
     * @return string Commission amount
     */
    public function calculate(string $volume): string
    {
        return bcmul($volume, self::COMMISSION_RATE, 8);
    }

    /**
     * Calculate net amount after commission
     *
     * @param  string  $volume  The USD volume of the trade
     * @return string Net amount (volume - commission)
     */
    public function getNetAmount(string $volume): string
    {
        $commission = $this->calculate($volume);

        return bcsub($volume, $commission, 8);
    }

    /**
     * Calculate total with commission included
     * Used for buy orders to know total USD needed
     *
     * @param  string  $volume  The base USD volume
     * @return string Total amount including commission
     */
    public function getTotalWithCommission(string $volume): string
    {
        $commission = $this->calculate($volume);

        return bcadd($volume, $commission, 8);
    }

    /**
     * Get commission rate as decimal string
     *
     * @return string Commission rate
     */
    public function getRate(): string
    {
        return self::COMMISSION_RATE;
    }

    /**
     * Get commission rate as percentage
     *
     * @return float Commission rate percentage
     */
    public function getRatePercentage(): float
    {
        return (float) bcmul(self::COMMISSION_RATE, '100', 2);
    }
}
