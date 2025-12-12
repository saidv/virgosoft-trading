<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create buyer with USD balance
        $this->buyer = User::factory()->create([
            'balance' => '100000.00000000',
        ]);

        // Create seller with BTC assets
        $this->seller = User::factory()->create([
            'balance' => '0.00000000',
        ]);

        Asset::factory()->create([
            'user_id' => $this->seller->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);
    }

    /**
     * Test complete trading flow: sell order placed first, then matching buy order
     */
    public function test_complete_trading_flow_buy_matches_sell(): void
    {
        // 1. Seller places sell order
        $sellResponse = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $sellResponse->assertStatus(201);
        $sellOrderId = $sellResponse->json('data.order.id');

        // Verify sell order is open
        $sellOrder = Order::find($sellOrderId);
        $this->assertEquals(OrderStatus::OPEN, $sellOrder->status);

        // Verify seller's BTC is locked
        $sellerAsset = Asset::where('user_id', $this->seller->id)
            ->where('symbol', 'BTC')
            ->first();
        $this->assertEquals('1.00000000', $sellerAsset->locked_amount);

        // 2. Buyer places matching buy order (same price and amount)
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'matched' => true,
                ],
            ]);

        $buyOrderId = $buyResponse->json('data.order.id');

        // 3. Verify both orders are filled
        $sellOrder->refresh();
        $buyOrder = Order::find($buyOrderId);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);

        // 4. Verify trade was created
        $this->assertDatabaseHas('trades', [
            'buy_order_id' => $buyOrderId,
            'sell_order_id' => $sellOrderId,
            'symbol' => 'BTC',
            'price' => '50000.00000000',
            'amount' => '1.00000000',
        ]);

        // 5. Verify seller received USD (full volume - commission paid by buyer)
        // Volume = 50000 * 1 = 50000
        // Seller receives full volume
        $this->seller->refresh();
        $this->assertEquals('50000.00000000', $this->seller->balance);

        // 6. Verify buyer received BTC asset
        $buyerAsset = Asset::where('user_id', $this->buyer->id)
            ->where('symbol', 'BTC')
            ->first();
        $this->assertNotNull($buyerAsset);
        $this->assertEquals('1.00000000', $buyerAsset->amount);

        // 7. Verify buyer's balance was deducted (volume + commission)
        // Total deducted = 50000 + 750 = 50750
        $this->buyer->refresh();
        $this->assertEquals('49250.00000000', $this->buyer->balance);

        // 8. Verify seller's BTC is no longer locked (was transferred)
        $sellerAsset->refresh();
        $this->assertEquals('9.00000000', $sellerAsset->amount);
        $this->assertEquals('0.00000000', $sellerAsset->locked_amount);
    }

    /**
     * Test trading flow: buy order placed first, then matching sell order
     */
    public function test_complete_trading_flow_sell_matches_buy(): void
    {
        // 1. Buyer places buy order first
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyResponse->assertStatus(201);
        $buyOrderId = $buyResponse->json('data.order.id');

        // Verify buy order is open
        $buyOrder = Order::find($buyOrderId);
        $this->assertEquals(OrderStatus::OPEN, $buyOrder->status);

        // Verify buyer's balance is reduced (locked for order)
        // Volume = 50000, Commission = 750, Total = 50750
        $this->buyer->refresh();
        $this->assertEquals('49250.00000000', $this->buyer->balance);

        // 2. Seller places matching sell order
        $sellResponse = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $sellResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'matched' => true,
                ],
            ]);

        $sellOrderId = $sellResponse->json('data.order.id');

        // 3. Verify both orders are filled
        $buyOrder->refresh();
        $sellOrder = Order::find($sellOrderId);
        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);

        // 4. Verify trade was created
        $this->assertDatabaseHas('trades', [
            'buy_order_id' => $buyOrderId,
            'sell_order_id' => $sellOrderId,
        ]);
    }

    /**
     * Test orders with different amounts do NOT match (full-match-only rule)
     */
    public function test_orders_with_different_amounts_do_not_match(): void
    {
        // 1. Seller places sell order for 1 BTC
        $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        // 2. Buyer places buy order for 0.5 BTC (different amount)
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '0.50000000',
            ]);

        $buyResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'matched' => false,
                ],
            ]);

        // Both orders should remain open
        $this->assertEquals(2, Order::where('status', OrderStatus::OPEN)->count());
    }

    /**
     * Test orders with mismatched prices do NOT match
     */
    public function test_orders_with_mismatched_prices_do_not_match(): void
    {
        // 1. Seller places sell order at $51000
        $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '51000.00000000',
                'amount' => '1.00000000',
            ]);

        // 2. Buyer places buy order at $50000 (below seller's ask)
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'matched' => false,
                ],
            ]);

        // Both orders should remain open
        $this->assertEquals(2, Order::where('status', OrderStatus::OPEN)->count());
    }

    /**
     * Test buy order matches with lower-priced sell order (price improvement)
     */
    public function test_buy_order_matches_with_lower_priced_sell(): void
    {
        // 1. Seller places sell order at $49000
        $sellResponse = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '49000.00000000',
                'amount' => '1.00000000',
            ]);

        $sellOrderId = $sellResponse->json('data.order.id');

        // 2. Buyer places buy order at $50000 (higher than seller's ask)
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'matched' => true,
                ],
            ]);

        // Trade should execute at the seller's price (better for buyer)
        $trade = Trade::where('sell_order_id', $sellOrderId)->first();
        $this->assertEquals('49000.00000000', $trade->price);
    }

    /**
     * Test sell order matches with higher-priced buy order
     */
    public function test_sell_order_matches_with_higher_priced_buy(): void
    {
        // 1. Buyer places buy order at $51000
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '51000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyOrderId = $buyResponse->json('data.order.id');

        // 2. Seller places sell order at $50000 (lower than buyer's bid)
        $sellResponse = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $sellResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'matched' => true,
                ],
            ]);

        // Trade should execute at the buyer's price (better for seller)
        $trade = Trade::where('buy_order_id', $buyOrderId)->first();
        $this->assertEquals('51000.00000000', $trade->price);
    }

    /**
     * Test user cannot match their own order
     */
    public function test_user_cannot_match_own_order(): void
    {
        // User has both USD and BTC
        Asset::factory()->create([
            'user_id' => $this->buyer->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // 1. User places sell order
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        // 2. Same user places matching buy order
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        // Order should be created but not matched
        $buyResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'matched' => false,
                ],
            ]);

        // No trades should exist
        $this->assertEquals(0, Trade::count());
    }

    /**
     * Test orders with different symbols do NOT match
     */
    public function test_orders_with_different_symbols_do_not_match(): void
    {
        // Give seller ETH
        Asset::factory()->create([
            'user_id' => $this->seller->id,
            'symbol' => 'ETH',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // 1. Seller places sell order for ETH
        $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'ETH',
                'side' => 'sell',
                'price' => '3000.00000000',
                'amount' => '1.00000000',
            ]);

        // 2. Buyer places buy order for BTC (different symbol)
        $buyResponse = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '3000.00000000',
                'amount' => '1.00000000',
            ]);

        $buyResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'matched' => false,
                ],
            ]);
    }

    /**
     * Test trades endpoint shows user's trades
     */
    public function test_user_can_view_their_trades(): void
    {
        // Execute a trade
        $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        // Both buyer and seller should see the trade
        $buyerTradesResponse = $this->actingAs($this->buyer, 'sanctum')
            ->getJson('/api/trades');

        $buyerTradesResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $sellerTradesResponse = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/trades');

        $sellerTradesResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test commission is correctly calculated and recorded
     */
    public function test_commission_is_correctly_calculated(): void
    {
        // Execute trade at $50000 for 1 BTC
        $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '50000.00000000',
                'amount' => '1.00000000',
            ]);

        // Commission should be 1.5% of 50000 = 750
        $trade = Trade::first();
        $this->assertEquals('750.00000000', $trade->commission);
    }
}
