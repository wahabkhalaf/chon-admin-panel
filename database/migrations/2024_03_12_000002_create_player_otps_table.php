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
        Schema::create('player_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')
                ->constrained('players')
                ->onDelete('cascade');
            $table->string('otp_code');
            $table->enum('purpose', ['login', 'registration', 'verification'])->default('login');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_otps');
    }
};