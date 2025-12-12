<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders');
            $table->foreignId('sell_order_id')->constrained('orders');
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('users');
            $table->string('symbol', 10);
            $table->decimal('price', 20, 8)->unsigned();
            $table->decimal('amount', 20, 8)->unsigned();
            $table->decimal('commission', 20, 8)->unsigned();
            $table->timestamps();

            // Indexes for trade history queries
            $table->index('symbol');
            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
