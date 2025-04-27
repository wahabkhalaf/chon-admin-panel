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
        Schema::create('prize_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->integer('rank_from');
            $table->integer('rank_to');
            $table->enum('prize_type', ['cash', 'item', 'points']);
            $table->decimal('prize_value', 10, 2);
            $table->timestamps();

            // Add index for faster queries
            $table->index(['competition_id', 'rank_from', 'rank_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prize_tiers');
    }
};