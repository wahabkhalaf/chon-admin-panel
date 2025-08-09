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
        Schema::create('player_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->json('delivery_data')->nullable(); // Store delivery confirmation data
            $table->timestamps();

            // Indexes for performance
            $table->index(['player_id', 'received_at']);
            $table->index(['player_id', 'read_at']);
            $table->unique(['player_id', 'notification_id']); // Prevent duplicates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_notifications');
    }
};
