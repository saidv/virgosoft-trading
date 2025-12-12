<?php

namespace Tests\Unit\Services;

use App\Models\Asset;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssetService::class);
    }

    // =========================================================================
    // add() Tests
    // =========================================================================

    public function test_add_creates_new_asset_if_not_exists(): void
    {
        $user = User::factory()->create();

        $this->service->add($user, 'BTC', '1.50000000');

        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', 'BTC')
            ->first();

        $this->assertNotNull($asset);
        $this->assertEquals('1.50000000', $asset->amount);
        $this->assertEquals('0.00000000', $asset->locked_amount);
    }

    public function test_add_increments_existing_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '2.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $this->service->add($user, 'BTC', '1.50000000');

        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', 'BTC')
            ->first();

        $this->assertEquals('3.50000000', $asset->amount);
    }

    public function test_add_throws_exception_for_negative_amount(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->add($user, 'BTC', '-1.00000000');
    }

    // =========================================================================
    // lock() Tests
    // =========================================================================

    public function test_lock_moves_amount_to_locked(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $this->service->lock($user, 'BTC', '2.00000000');

        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', 'BTC')
            ->first();

        // Lock decreases amount and increases locked_amount
        $this->assertEquals('3.00000000', $asset->amount);
        $this->assertEquals('2.00000000', $asset->locked_amount);
    }

    public function test_lock_throws_exception_when_asset_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No BTC asset found');

        $this->service->lock($user, 'BTC', '1.00000000');
    }

    public function test_lock_throws_exception_when_insufficient_available(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.50000000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient BTC');

        $this->service->lock($user, 'BTC', '1.00000000');
    }

    public function test_lock_throws_exception_for_negative_amount(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->lock($user, 'BTC', '-1.00000000');
    }

    // =========================================================================
    // unlock() Tests
    // =========================================================================

    public function test_unlock_moves_locked_back_to_available(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '2.00000000',
        ]);

        $this->service->unlock($user, 'BTC', '1.00000000');

        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', 'BTC')
            ->first();

        // Unlock increases amount and decreases locked_amount
        $this->assertEquals('6.00000000', $asset->amount);
        $this->assertEquals('1.00000000', $asset->locked_amount);
    }

    public function test_unlock_throws_exception_when_asset_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No BTC asset found');

        $this->service->unlock($user, 'BTC', '1.00000000');
    }

    public function test_unlock_throws_exception_when_unlocking_more_than_locked(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '1.00000000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot unlock more than locked amount');

        $this->service->unlock($user, 'BTC', '2.00000000');
    }

    // =========================================================================
    // transferLocked() Tests
    // =========================================================================

    public function test_transfer_locked_moves_asset_from_seller_to_buyer(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        // Seller has 5 BTC available + 2 BTC locked (simulates after lock was called)
        // Total seller owns: 5 + 2 = 7 BTC originally, but 2 BTC was locked for sell order
        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000', // This is what remains after lock deducted 2
            'locked_amount' => '2.00000000', // This was locked for the sell order
        ]);

        Asset::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $this->service->transferLocked($seller, $buyer, 'BTC', '2.00000000');

        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', 'BTC')
            ->first();
        $buyerAsset = Asset::where('user_id', $buyer->id)
            ->where('symbol', 'BTC')
            ->first();

        // Seller: amount stays at 5 (was already reduced during lock), locked becomes 0
        $this->assertEquals('5.00000000', $sellerAsset->amount);
        $this->assertEquals('0.00000000', $sellerAsset->locked_amount);
        // Buyer: 1 + 2 = 3 BTC
        $this->assertEquals('3.00000000', $buyerAsset->amount);
    }

    public function test_transfer_locked_creates_buyer_asset_if_not_exists(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '2.00000000',
        ]);

        $this->service->transferLocked($seller, $buyer, 'BTC', '2.00000000');

        $buyerAsset = Asset::where('user_id', $buyer->id)
            ->where('symbol', 'BTC')
            ->first();

        $this->assertNotNull($buyerAsset);
        $this->assertEquals('2.00000000', $buyerAsset->amount);
    }

    public function test_transfer_locked_throws_when_seller_has_no_asset(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seller has no BTC asset');

        $this->service->transferLocked($seller, $buyer, 'BTC', '1.00000000');
    }

    public function test_transfer_locked_throws_when_insufficient_locked(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '1.00000000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seller has insufficient locked BTC');

        $this->service->transferLocked($seller, $buyer, 'BTC', '2.00000000');
    }

    // =========================================================================
    // hasSufficientAsset() Tests
    // =========================================================================

    public function test_has_sufficient_asset_returns_true_when_enough(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '2.00000000',
        ]);

        // Available = 5 - 2 = 3
        $result = $this->service->hasSufficientAsset($user, 'BTC', '2.00000000');

        $this->assertTrue($result);
    }

    public function test_has_sufficient_asset_returns_false_when_not_enough(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '4.00000000',
        ]);

        // Available = 5 - 4 = 1
        $result = $this->service->hasSufficientAsset($user, 'BTC', '2.00000000');

        $this->assertFalse($result);
    }

    public function test_has_sufficient_asset_returns_false_when_no_asset(): void
    {
        $user = User::factory()->create();

        $result = $this->service->hasSufficientAsset($user, 'BTC', '1.00000000');

        $this->assertFalse($result);
    }

    public function test_has_sufficient_asset_returns_true_when_exactly_equal(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '3.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $result = $this->service->hasSufficientAsset($user, 'BTC', '3.00000000');

        $this->assertTrue($result);
    }
}
