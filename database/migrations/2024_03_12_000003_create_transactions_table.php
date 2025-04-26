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
            $table->unsignedBigInteger('competition_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', [
                'entry_fee',
                'prize',
                'bonus',
                'refund'
            ]);
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'cancelled',
                'refunded'
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