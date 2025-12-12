<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

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
            'amount' => $this->faker->randomFloat(8, 0, 100),
            'locked_amount' => '0.00000000',
        ];
    }

    /**
     * Indicate that the asset is BTC.
     */
    public function btc(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Indicate that the asset is ETH.
     */
    public function eth(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'ETH',
        ]);
    }

    /**
     * Indicate that some amount is locked.
     */
    public function withLockedAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_amount' => $amount,
        ]);
    }
}
