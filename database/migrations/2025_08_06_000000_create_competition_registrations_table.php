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
        Schema::create('competition_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');

            // Registration status with comprehensive states
            $table->enum('registration_status', [
                'pending_payment',    // Waiting for payment (if entry_fee > 0)
                'payment_processing', // Payment in progress
                'registered',         // Successfully registered (paid or free)
                'payment_failed',     // Payment failed
                'cancelled',          // Registration cancelled
                'refunded',          // Entry fee refunded
                'expired'            // Payment window expired
            ])->default('pending_payment');

            // Payment tracking
            $table->decimal('entry_fee_paid', 10, 2)->default(0);
            $table->boolean('is_free_entry')->default(false);

            // Timing (all in UTC, but logic handles Baghdad timezone)
            $table->timestamp('registered_at')->nullable(); // When successfully registered
            $table->timestamp('expires_at')->nullable();    // For pending payments (15 min window)
            $table->timestamps();

            // Additional metadata
            $table->string('registration_source', 50)->default('mobile_app'); // 'mobile_app', 'web', 'admin'
            $table->text('notes')->nullable(); // Admin notes or payment gateway responses

            // Constraints
            $table->unique(['competition_id', 'player_id']); // One registration per player per competition
        });

        // Create indexes for performance
        Schema::table('competition_registrations', function (Blueprint $table) {
            $table->index('competition_id');
            $table->index('player_id');
            $table->index('registration_status');
            $table->index('expires_at');
            $table->index('registered_at');
            $table->index(['competition_id', 'registration_status']);
            $table->index(['player_id', 'registration_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_registrations');
    }
};