<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Competition name');
            $table->text('description')->nullable()->comment('Competition description');
            $table->decimal('entry_fee', 10, 2)->comment('Entry fee must be non-negative');
            $table->decimal('prize_pool', 10, 2)->comment('Prize pool must be non-negative');
            $table->timestamp('start_time')->comment('Competition start time');
            $table->timestamp('end_time')->comment('Competition end time');
            $table->integer('max_users')->comment('Maximum number of users allowed');
            $table->enum('status', ['upcoming', 'active', 'completed', 'closed'])
                ->default('upcoming')
                ->comment('Current status of the session');
            $table->timestamps();

            // Constraints
            // $table->check('entry_fee >= 0');
            // $table->check('prize_pool >= 0');
            // $table->check('max_users > 0');
            // $table->check('end_time > start_time');

            // Indexes
            $table->index('name');
            $table->index('status');
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
