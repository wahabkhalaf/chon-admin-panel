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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_number')->unique();
            $table->string('nickname')->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('level')->default(1);
            $table->string('language')->default('en');
            $table->integer('experience_points')->default(0);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};