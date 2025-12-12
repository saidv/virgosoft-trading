<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private BalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BalanceService::class);
    }

    // =========================================================================
    // add() Tests
    // =========================================================================

    public function test_add_increments_user_balance(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->service->add($user, '500.00000000');

        $user->refresh();
        $this->assertEquals('1500.00000000', $user->balance);
    }

    public function test_add_throws_exception_for_negative_amount(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->add($user, '-100.00000000');
    }

    public function test_add_accepts_zero_amount(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->service->add($user, '0.00000000');

        $user->refresh();
        $this->assertEquals('1000.00000000', $user->balance);
    }

    // =========================================================================
    // deduct() Tests
    // =========================================================================

    public function test_deduct_decrements_user_balance(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->service->deduct($user, '300.00000000');

        $user->refresh();
        $this->assertEquals('700.00000000', $user->balance);
    }

    public function test_deduct_throws_exception_for_negative_amount(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->deduct($user, '-100.00000000');
    }

    // =========================================================================
    // hasSufficientBalance() Tests
    // =========================================================================

    public function test_has_sufficient_balance_returns_true_when_balance_greater(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $result = $this->service->hasSufficientBalance($user, '500.00000000');

        $this->assertTrue($result);
    }

    public function test_has_sufficient_balance_returns_true_when_balance_equal(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $result = $this->service->hasSufficientBalance($user, '1000.00000000');

        $this->assertTrue($result);
    }

    public function test_has_sufficient_balance_returns_false_when_balance_less(): void
    {
        $user = User::factory()->create(['balance' => '500.00000000']);

        $result = $this->service->hasSufficientBalance($user, '1000.00000000');

        $this->assertFalse($result);
    }

    public function test_has_sufficient_balance_handles_decimal_precision(): void
    {
        $user = User::factory()->create(['balance' => '0.00000001']);

        $this->assertTrue($this->service->hasSufficientBalance($user, '0.00000001'));
        $this->assertFalse($this->service->hasSufficientBalance($user, '0.00000002'));
    }

    // =========================================================================
    // lockForOrder() Tests
    // =========================================================================

    public function test_lock_for_order_deducts_from_balance(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $this->service->lockForOrder($user, '5000.00000000');

        $user->refresh();
        $this->assertEquals('5000.00000000', $user->balance);
    }

    // =========================================================================
    // releaseForOrder() Tests
    // =========================================================================

    public function test_release_for_order_adds_to_balance(): void
    {
        $user = User::factory()->create(['balance' => '5000.00000000']);

        $this->service->releaseForOrder($user, '2000.00000000');

        $user->refresh();
        $this->assertEquals('7000.00000000', $user->balance);
    }

    // =========================================================================
    // credit() Tests
    // =========================================================================

    public function test_credit_adds_to_balance(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $this->service->credit($user, '250.00000000');

        $user->refresh();
        $this->assertEquals('1250.00000000', $user->balance);
    }
}
