<?php

namespace Database\Factories;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => $this->faker->randomElement(['BTC', 'ETH']),
            'side' => $this->faker->randomElement([OrderSide::BUY, OrderSide::SELL]),
            'price' => $this->faker->randomFloat(8, 100, 100000),
            'amount' => $this->faker->randomFloat(8, 0.01, 10),
            'status' => OrderStatus::OPEN,
        ];
    }

    /**
     * Indicate that the order is a buy order.
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => OrderSide::BUY,
        ]);
    }

    /**
     * Indicate that the order is a sell order.
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => OrderSide::SELL,
        ]);
    }

    /**
     * Indicate that the order is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::OPEN,
        ]);
    }

    /**
     * Indicate that the order is filled.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::FILLED,
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    /**
     * Set a specific symbol.
     */
    public function btc(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Set a specific symbol.
     */
    public function eth(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'ETH',
        ]);
    }
}
