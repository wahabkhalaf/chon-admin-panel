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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', [
                'pending',
                'completed',
                'failed'
            ])->default('pending');
            $table->string('payment_method')->nullable(); // e.g., 'credit_card', 'paypal', 'bank_transfer'
            $table->string('payment_provider')->nullable(); // e.g., 'stripe', 'paypal', 'razorpay'
            $table->json('payment_details')->nullable(); // Store masked card info, transaction IDs, etc.
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};