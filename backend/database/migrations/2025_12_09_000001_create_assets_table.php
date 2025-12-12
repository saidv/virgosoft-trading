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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10); // BTC, ETH, etc.
            $table->decimal('amount', 20, 8)->unsigned()->default(0);
            $table->decimal('locked_amount', 20, 8)->unsigned()->default(0);
            $table->timestamps();

            // Unique constraint: one asset per symbol per user
            $table->unique(['user_id', 'symbol']);

            // Index for queries
            $table->index(['user_id', 'symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
