<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a buyer with USD balance (ready to buy crypto)
        $buyer = User::create([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.com',
            'password' => Hash::make('password'),
            'balance' => '10000.00000000', // $10,000 USD
        ]);

        // Create a seller with crypto assets (ready to sell)
        $seller = User::create([
            'name' => 'Test Seller',
            'email' => 'seller@test.com',
            'password' => Hash::make('password'),
            'balance' => '5000.00000000', // $5,000 USD
        ]);

        // Create a market maker for realistic orderbook
        $marketMaker = User::create([
            'name' => 'Market Maker',
            'email' => 'mm@test.com',
            'password' => Hash::make('password'),
            'balance' => '100000.00000000', // $100,000 USD
        ]);

        // Give the seller some crypto assets
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '2.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'ETH',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Give the buyer some ETH too for testing sell orders
        Asset::create([
            'user_id' => $buyer->id,
            'symbol' => 'ETH',
            'amount' => '5.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Market maker has lots of crypto
        Asset::create([
            'user_id' => $marketMaker->id,
            'symbol' => 'BTC',
            'amount' => '50.00000000',
            'locked_amount' => '10.00000000', // Some locked in orders
        ]);

        Asset::create([
            'user_id' => $marketMaker->id,
            'symbol' => 'ETH',
            'amount' => '500.00000000',
            'locked_amount' => '50.00000000',
        ]);

        // =====================================================================
        // Create BTC/USD Orderbook
        // =====================================================================

        // BTC Sell orders (asks) - prices above market
        $btcAsks = [
            ['price' => '43500.00000000', 'amount' => '0.50000000'],
            ['price' => '43400.00000000', 'amount' => '0.75000000'],
            ['price' => '43300.00000000', 'amount' => '1.00000000'],
            ['price' => '43200.00000000', 'amount' => '0.25000000'],
            ['price' => '43100.00000000', 'amount' => '0.80000000'],
        ];

        foreach ($btcAsks as $ask) {
            Order::create([
                'user_id' => $marketMaker->id,
                'symbol' => 'BTC',
                'side' => 'sell',
                'price' => $ask['price'],
                'amount' => $ask['amount'],
                'status' => 1, // open
            ]);
        }

        // BTC Buy orders (bids) - prices below market
        $btcBids = [
            ['price' => '42900.00000000', 'amount' => '0.60000000'],
            ['price' => '42800.00000000', 'amount' => '1.20000000'],
            ['price' => '42700.00000000', 'amount' => '0.45000000'],
            ['price' => '42600.00000000', 'amount' => '2.00000000'],
            ['price' => '42500.00000000', 'amount' => '1.50000000'],
        ];

        foreach ($btcBids as $bid) {
            Order::create([
                'user_id' => $marketMaker->id,
                'symbol' => 'BTC',
                'side' => 'buy',
                'price' => $bid['price'],
                'amount' => $bid['amount'],
            ]);
        }

        // =====================================================================
        // Create ETH/USD Orderbook
        // =====================================================================

        // ETH Sell orders (asks)
        $ethAsks = [
            ['price' => '2350.00000000', 'amount' => '5.00000000'],
            ['price' => '2340.00000000', 'amount' => '8.00000000'],
            ['price' => '2330.00000000', 'amount' => '3.50000000'],
            ['price' => '2320.00000000', 'amount' => '10.00000000'],
            ['price' => '2310.00000000', 'amount' => '6.00000000'],
        ];

        foreach ($ethAsks as $ask) {
            Order::create([
                'user_id' => $marketMaker->id,
                'symbol' => 'ETH',
                'side' => 'sell',
                'price' => $ask['price'],
                'amount' => $ask['amount'],
                'status' => 1,
            ]);
        }

        // ETH Buy orders (bids)
        $ethBids = [
            ['price' => '2290.00000000', 'amount' => '4.00000000'],
            ['price' => '2280.00000000', 'amount' => '12.00000000'],
            ['price' => '2270.00000000', 'amount' => '7.50000000'],
            ['price' => '2260.00000000', 'amount' => '15.00000000'],
            ['price' => '2250.00000000', 'amount' => '20.00000000'],
        ];

        foreach ($ethBids as $bid) {
            Order::create([
                'user_id' => $marketMaker->id,
                'symbol' => 'ETH',
                'side' => 'buy',
                'price' => $bid['price'],
                'amount' => $bid['amount'],
            ]);
        }

        // Add some orders from buyer/seller for variety
        Order::create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '42000.00000000',
            'amount' => '0.10000000',
        ]);

        Order::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '44000.00000000',
            'amount' => '0.50000000',
        ]);

        $this->command->info('');
        $this->command->info('✅ Test users created:');
        $this->command->info('   Buyer:  buyer@test.com / password ($10,000 USD, 5 ETH)');
        $this->command->info('   Seller: seller@test.com / password ($5,000 USD, 2 BTC, 10 ETH)');
        $this->command->info('   MM:     mm@test.com / password ($100,000 USD, 50 BTC, 500 ETH)');
        $this->command->info('');
        $this->command->info('📊 Orderbook seeded:');
        $this->command->info('   BTC/USD: 5 bids ($42,500-$42,900) | 5 asks ($43,100-$43,500)');
        $this->command->info('   ETH/USD: 5 bids ($2,250-$2,290)   | 5 asks ($2,310-$2,350)');
    }
}
