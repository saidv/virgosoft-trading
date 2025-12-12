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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10); // BTC, ETH, etc.
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 20, 8)->unsigned();
            $table->decimal('amount', 20, 8)->unsigned();
            $table->tinyInteger('status')->default(1); // 1=open, 2=filled, 3=cancelled
            $table->timestamps();

            // Composite index for orderbook queries
            $table->index(['symbol', 'side', 'status', 'price']);

            // Index for user's orders
            $table->index(['user_id', 'status']);

            // Index for time-priority matching
            $table->index(['created_at']);

            // Index for matching queries
            $table->index(['symbol', 'status', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
