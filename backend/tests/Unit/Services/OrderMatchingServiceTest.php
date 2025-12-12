<?php

namespace Tests\Unit\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Services\OrderMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderMatchingService::class);
    }

    // =========================================================================
    // matchOrder() - No Match Cases
    // =========================================================================

    public function test_match_order_returns_null_when_order_not_found(): void
    {
        // Create a dummy order then delete it to simulate not found
        $user = User::factory()->create(['balance' => '100000.00000000']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
        ]);

        $orderId = $order->id;
        $order->delete();

        // Create a new order object with the deleted ID
        $fakeOrder = new Order;
        $fakeOrder->id = $orderId;

        $result = $this->service->matchOrder($fakeOrder);

        $this->assertNull($result);
    }

    public function test_match_order_returns_null_when_order_not_open(): void
    {
        $user = User::factory()->create(['balance' => '100000.00000000']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
        ]);

        $result = $this->service->matchOrder($order);

        $this->assertNull($result);
    }

    public function test_match_order_returns_null_when_no_matching_order_exists(): void
    {
        $buyer = User::factory()->create(['balance' => '100000.00000000']);

        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // No sell orders exist
        $result = $this->service->matchOrder($buyOrder);

        $this->assertNull($result);
    }

    public function test_match_order_returns_null_when_amounts_differ(): void
    {
        $buyer = User::factory()->create(['balance' => '100000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '2.00000000',
        ]);

        // Seller has open sell order for 2 BTC
        Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '50000.00000000',
            'amount' => '2.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // Buyer order for 1 BTC (different amount)
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $result = $this->service->matchOrder($buyOrder);

        $this->assertNull($result);
    }

    public function test_match_order_returns_null_when_prices_dont_cross(): void
    {
        $buyer = User::factory()->create(['balance' => '100000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '1.00000000',
        ]);

        // Seller asking 51000
        Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '51000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // Buyer bidding 50000 (below ask)
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $result = $this->service->matchOrder($buyOrder);

        $this->assertNull($result);
    }

    public function test_match_order_returns_null_when_same_user(): void
    {
        $user = User::factory()->create(['balance' => '100000.00000000']);

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '1.00000000',
        ]);

        // User's own sell order
        Order::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // Same user's buy order
        $buyOrder = Order::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $result = $this->service->matchOrder($buyOrder);

        $this->assertNull($result);
    }

    // =========================================================================
    // matchOrder() - Successful Match Cases
    // =========================================================================

    public function test_match_order_executes_trade_when_buy_matches_sell(): void
    {
        // Scenario: Buy order for 1 BTC at 50000 matches Sell order for 1 BTC at 50000
        // Trade value = 50000, Commission (1.5%) = 750, Total = 50750

        // Buyer: Balance was ALREADY deducted when order was placed (50750)
        // Start with 0 balance because the lock already happened
        $buyer = User::factory()->create(['balance' => '0.00000000']);

        // Seller: Has BTC locked when order was placed
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '1.00000000', // 1 BTC locked for sell order
        ]);

        // Sell order exists (maker)
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // Buy order matches (taker)
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $trade = $this->service->matchOrder($buyOrder);

        // Verify trade was created
        $this->assertNotNull($trade);
        $this->assertInstanceOf(Trade::class, $trade);
        $this->assertEquals($buyOrder->id, $trade->buy_order_id);
        $this->assertEquals($sellOrder->id, $trade->sell_order_id);
        $this->assertEquals('BTC', $trade->symbol);
        $this->assertEquals('50000.00000000', $trade->price);
        $this->assertEquals('1.00000000', $trade->amount);

        // Verify both orders are filled
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);

        // Verify balances updated
        $buyer->refresh();
        $seller->refresh();

        // Buyer: Balance stays at 0 (was already deducted at order placement)
        $this->assertEquals('0.00000000', $buyer->balance);

        // Seller: Receives full volume (50000), commission is paid by buyer
        $this->assertEquals('50000.00000000', $seller->balance);

        // Verify assets updated
        $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', 'BTC')->first();
        $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', 'BTC')->first();

        // Buyer receives 1 BTC
        $this->assertEquals('1.00000000', $buyerAsset->amount);

        // Seller: amount stays at 5 (was already reduced when order was placed/locked)
        // The lock() reduced amount from original, locked_amount now goes to 0
        $this->assertEquals('5.00000000', $sellerAsset->amount);
        $this->assertEquals('0.00000000', $sellerAsset->locked_amount);
    }

    public function test_match_order_executes_at_maker_price_when_buy_price_higher(): void
    {
        // Scenario: Buyer bids 60000, Seller asks 50000, trade executes at 50000
        // Buyer locked: 60000 + 900 commission (1.5% of 60000) = 60900
        // At execution: Trade at 50000, buyer gets refund of price difference
        // Balance starts at 0 (was deducted at order placement)
        $buyer = User::factory()->create(['balance' => '0.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '5.00000000',
            'locked_amount' => '1.00000000',
        ]);

        // Seller asking 50000 (maker order)
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        // Buyer willing to pay 60000 (taker order)
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '60000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $trade = $this->service->matchOrder($buyOrder);

        // Trade should execute at seller's (maker) price
        $this->assertNotNull($trade);
        $this->assertEquals('50000.00000000', $trade->price);

        // Verify buyer gets refund for price improvement
        // Locked: 60000 + 900 = 60900
        // Actual cost: 50000 + 750 = 50750
        // Refund: 60900 - 50750 = 10150
        $buyer->refresh();
        $this->assertEquals('10150.00000000', $buyer->balance);
    }

    public function test_match_order_commission_is_calculated_correctly(): void
    {
        // Scenario: Buy 1 ETH at 10000
        // Trade value = 10000, Commission = 150 (1.5%)
        // Buyer balance starts at 0 (was deducted at order placement)
        $buyer = User::factory()->create(['balance' => '0.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'ETH',
            'amount' => '10.00000000',
            'locked_amount' => '1.00000000',
        ]);

        Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'ETH',
            'side' => OrderSide::SELL,
            'price' => '10000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'ETH',
            'side' => OrderSide::BUY,
            'price' => '10000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $trade = $this->service->matchOrder($buyOrder);

        // Commission = 10000 * 0.015 = 150
        $this->assertNotNull($trade);
        $this->assertEquals('150.00000000', $trade->commission);
    }
}
