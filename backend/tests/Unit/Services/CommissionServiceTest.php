<?php

namespace Tests\Unit\Services;

use App\Services\CommissionService;
use PHPUnit\Framework\TestCase;

class CommissionServiceTest extends TestCase
{
    private CommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommissionService;
    }

    // =========================================================================
    // calculate() Tests
    // =========================================================================

    public function test_calculate_returns_correct_commission_for_simple_volume(): void
    {
        // 1.5% of $1000 = $15
        $result = $this->service->calculate('1000.00000000');
        $this->assertEquals('15.00000000', $result);
    }

    public function test_calculate_returns_correct_commission_for_large_volume(): void
    {
        // 1.5% of $50,000 = $750
        $result = $this->service->calculate('50000.00000000');
        $this->assertEquals('750.00000000', $result);
    }

    public function test_calculate_handles_decimal_volumes(): void
    {
        // 1.5% of $123.45678901 = $1.85185184 (truncated to 8 decimals)
        $result = $this->service->calculate('123.45678901');
        $this->assertEquals('1.85185183', $result);
    }

    public function test_calculate_returns_zero_for_zero_volume(): void
    {
        $result = $this->service->calculate('0.00000000');
        $this->assertEquals('0.00000000', $result);
    }

    public function test_calculate_handles_very_small_volume(): void
    {
        // 1.5% of $0.00000001 = $0.00000000 (too small)
        $result = $this->service->calculate('0.00000001');
        $this->assertEquals('0.00000000', $result);
    }

    // =========================================================================
    // getNetAmount() Tests
    // =========================================================================

    public function test_get_net_amount_returns_volume_minus_commission(): void
    {
        // $1000 - 1.5% = $1000 - $15 = $985
        $result = $this->service->getNetAmount('1000.00000000');
        $this->assertEquals('985.00000000', $result);
    }

    public function test_get_net_amount_for_large_volume(): void
    {
        // $50,000 - 1.5% = $50,000 - $750 = $49,250
        $result = $this->service->getNetAmount('50000.00000000');
        $this->assertEquals('49250.00000000', $result);
    }

    public function test_get_net_amount_handles_decimals(): void
    {
        // $100.50 - 1.5% = $100.50 - $1.5075 = $98.9925
        $result = $this->service->getNetAmount('100.50000000');
        $this->assertEquals('98.99250000', $result);
    }

    // =========================================================================
    // getTotalWithCommission() Tests
    // =========================================================================

    public function test_get_total_with_commission_adds_commission_to_volume(): void
    {
        // $1000 + 1.5% = $1000 + $15 = $1015
        $result = $this->service->getTotalWithCommission('1000.00000000');
        $this->assertEquals('1015.00000000', $result);
    }

    public function test_get_total_with_commission_for_large_volume(): void
    {
        // $50,000 + 1.5% = $50,000 + $750 = $50,750
        $result = $this->service->getTotalWithCommission('50000.00000000');
        $this->assertEquals('50750.00000000', $result);
    }

    public function test_get_total_with_commission_handles_decimals(): void
    {
        // $100.50 + 1.5% = $100.50 + $1.5075 = $102.0075
        $result = $this->service->getTotalWithCommission('100.50000000');
        $this->assertEquals('102.00750000', $result);
    }

    // =========================================================================
    // getRate() and getRatePercentage() Tests
    // =========================================================================

    public function test_get_rate_returns_decimal_rate(): void
    {
        $result = $this->service->getRate();
        $this->assertEquals('0.015', $result);
    }

    public function test_get_rate_percentage_returns_percentage(): void
    {
        $result = $this->service->getRatePercentage();
        $this->assertEquals(1.5, $result);
    }

    // =========================================================================
    // Consistency Tests
    // =========================================================================

    public function test_net_amount_plus_commission_equals_original_volume(): void
    {
        $volume = '1000.00000000';
        $net = $this->service->getNetAmount($volume);
        $commission = $this->service->calculate($volume);

        // net + commission should equal original volume
        $reconstructed = bcadd($net, $commission, 8);
        $this->assertEquals($volume, $reconstructed);
    }

    public function test_total_with_commission_minus_commission_equals_original(): void
    {
        $volume = '1000.00000000';
        $total = $this->service->getTotalWithCommission($volume);
        $commission = $this->service->calculate($volume);

        // total - commission should equal original volume
        $reconstructed = bcsub($total, $commission, 8);
        $this->assertEquals($volume, $reconstructed);
    }
}
