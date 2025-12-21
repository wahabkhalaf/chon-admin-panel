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
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->enum('type', ['purchase', 'spend', 'admin_credit', 'refund']);
            $table->integer('amount');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->enum('reference_type', ['competition', 'package_purchase', 'admin_action'])->nullable();
            $table->string('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->onDelete('cascade');

            $table->index(['player_id', 'created_at']);
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
    }
};
