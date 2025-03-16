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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "Credit Card", "PayPal")
            $table->string('code')->unique(); // System code (e.g., "credit_card", "paypal")
            $table->string('provider'); // Payment provider (e.g., "stripe", "paypal", "razorpay")
            $table->string('icon')->nullable(); // Icon path or class
            $table->json('config')->nullable(); // Configuration for the payment method
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_deposit')->default(false);
            $table->boolean('supports_withdrawal')->default(false);
            $table->decimal('min_amount', 10, 2)->default(0);
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->decimal('fee_fixed', 10, 2)->default(0); // Fixed fee amount
            $table->decimal('fee_percentage', 5, 2)->default(0); // Percentage fee
            $table->integer('processing_time_hours')->default(0); // Estimated processing time in hours
            $table->text('instructions')->nullable(); // Instructions for users
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};