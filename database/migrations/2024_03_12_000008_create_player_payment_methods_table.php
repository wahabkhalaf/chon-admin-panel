<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->string('token')->nullable(); // Payment token from provider
            $table->string('external_id')->nullable(); // External ID from payment provider
            $table->string('nickname')->nullable(); // User-defined nickname (e.g., "My Visa Card")
            $table->json('details')->nullable(); // Masked card info, bank account details, etc.
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Ensure a player can't have duplicate payment methods
            $table->unique(['player_id', 'payment_method_id', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_payment_methods');
    }
};