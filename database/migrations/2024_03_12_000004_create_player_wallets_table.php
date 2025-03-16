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
        Schema::create('player_wallets', function (Blueprint $table) {
            $table->foreignId('player_id')->primary()->constrained('players')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_wallets');
    }
};