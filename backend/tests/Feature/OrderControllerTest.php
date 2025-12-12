<?php

namespace Tests\Feature;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'balance' => '10000.00000000',
        ]);
    }

    /**
     * Test get orderbook requires symbol parameter
     */
    public function test_orderbook_requires_symbol(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    /**
     * Test get orderbook with valid symbol
     */
    public function test_orderbook_returns_buy_and_sell_orders(): void
    {
        // Create some orders
        $buyer = User::factory()->create(['balance' => '50000.00000000']);
        $seller = User::factory()->create();
        Asset::factory()->create(['user_id' => $seller->id, 'symbol' => 'BTC', 'amount' => '10.00000000']);

        Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '49000.00000000',
            'amount' => '0.50000000',
            'status' => OrderStatus::OPEN,
        ]);

        Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '51000.00000000',
            'amount' => '0.30000000',
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders?symbol=BTC');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'symbol',
                    'buy_orders',
                    'sell_orders',
                    'best_bid',
                    'best_ask',
                    'spread',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'symbol' => 'BTC',
                    'best_bid' => '49000.00000000',
                    'best_ask' => '51000.00000000',
                    'spread' => '2000.00000000',
                ],
            ]);
    }

    /**
     * Test orderbook rejects invalid symbol
     */
    public function test_orderbook_rejects_invalid_symbol(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders?symbol=INVALID');

        $response->assertStatus(422);
    }

    /**
     * Test my orders requires authentication
     */
    public function test_my_orders_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders/my');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can see their orders
     */
    public function test_authenticated_user_can_see_their_orders(): void
    {
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders/my');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                ],
            ]);
    }

    /**
     * Test place order requires authentication
     */
    public function test_place_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00',
            'amount' => '0.1',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can place buy order with sufficient balance
     */
    public function test_user_can_place_buy_order_with_sufficient_balance(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '100.00000000',
                'amount' => '1.00000000',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order' => [
                        'id',
                        'symbol',
                        'side',
                        'price',
                        'amount',
                        'status',
                        'status_label',
                        'total_value',
                        'commission',
                    ],
                    'matched',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'order' => [
                        'symbol' => 'BTC',
                        'side' => 'buy',
                    ],
                ],
            ]);

        // Verify order exists in database (without enum string values that differ between SQLite/PostgreSQL)
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Test buy order fails with insufficient balance
     */
    public function test_buy_order_fails_with_insufficient_balance(): void
    {
        $this->user->update(['balance' => '50.00000000']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '100.00000000',
                'amount' => '1.00000000',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Insufficient balance', $response->json('message'));
    }

    /**
     * Test user can place sell order with sufficient asset
     */
    public function test_user_can_place_sell_order_with_sufficient_asset(): void
    {
        // Give user some BTC
        Asset::factory()->create([
            'user_id' => $this->user->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '0.50000000',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order' => [
                        'symbol' => 'BTC',
                        'side' => 'sell',
                    ],
                ],
            ]);
    }

    /**
     * Test sell order fails with insufficient asset
     */
    public function test_sell_order_fails_with_insufficient_asset(): void
    {
        // User has no assets
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => '50000.00000000',
                'amount' => '0.50000000',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Insufficient BTC', $response->json('message'));
    }

    /**
     * Test order validation - missing fields
     */
    public function test_order_validation_missing_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }

    /**
     * Test order validation - invalid symbol
     */
    public function test_order_validation_invalid_symbol(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'INVALID',
                'side' => 'buy',
                'price' => '100.00',
                'amount' => '1.00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    /**
     * Test order validation - invalid side
     */
    public function test_order_validation_invalid_side(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'hold',
                'price' => '100.00',
                'amount' => '1.00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['side']);
    }

    /**
     * Test order validation - price must be positive
     */
    public function test_order_validation_price_must_be_positive(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '0',
                'amount' => '1.00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test order validation - amount must be positive
     */
    public function test_order_validation_amount_must_be_positive(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => '100.00',
                'amount' => '-1.00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test cancel order requires authentication
     */
    public function test_cancel_order_requires_authentication(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(401);
    }

    /**
     * Test user can cancel their own open buy order
     */
    public function test_user_can_cancel_own_open_buy_order(): void
    {
        // Deduct some balance to simulate locked funds
        $this->user->update(['balance' => '9000.00000000']);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'symbol' => 'BTC',
            'side' => OrderSide::BUY,
            'price' => '100.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'status' => OrderStatus::CANCELLED->value, // Integer value (3)
                        'status_label' => 'Cancelled',
                    ],
                ],
                'message' => 'Order cancelled successfully',
            ]);

        // Verify in database using enum value
        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
    }

    /**
     * Test user can cancel their own open sell order
     */
    public function test_user_can_cancel_own_open_sell_order(): void
    {
        // Create asset with locked amount
        Asset::factory()->create([
            'user_id' => $this->user->id,
            'symbol' => 'BTC',
            'amount' => '2.00000000',
            'locked_amount' => '1.00000000',
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'symbol' => 'BTC',
            'side' => OrderSide::SELL,
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ]);
    }

    /**
     * Test user cannot cancel another user's order
     */
    public function test_user_cannot_cancel_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => OrderStatus::OPEN,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    /**
     * Test cannot cancel filled order
     */
    public function test_cannot_cancel_filled_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::FILLED,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled (not open)',
            ]);
    }

    /**
     * Test cannot cancel already cancelled order
     */
    public function test_cannot_cancel_already_cancelled_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::CANCELLED,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled (not open)',
            ]);
    }

    /**
     * Test cancel nonexistent order
     */
    public function test_cancel_nonexistent_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders/99999/cancel');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }
}
