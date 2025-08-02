<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Competition name');
            $table->text('description')->nullable()->comment('Competition description');
            $table->string('name_kurdish', 100)->nullable()->comment('Competition name in Kurdish');
            $table->text('description_kurdish')->nullable()->comment('Competition description in Kurdish');
            $table->decimal('entry_fee', 10, 2)->comment('Entry fee must be non-negative');
            $table->timestamp('open_time')->comment('Registration opening time');
            $table->timestamp('start_time')->comment('Competition start time');
            $table->timestamp('end_time')->comment('Competition end time');
            $table->integer('max_users')->comment('Maximum number of users allowed');
            $table->string('game_type')->comment('Type of game for this competition');
            
            $table->timestamps();

            // Constraints
            // $table->check('entry_fee >= 0');
            // $table->check('max_users > 0');
            // $table->check('end_time > start_time');

            // Indexes
            $table->index('name');
            $table->index('open_time');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('game_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
