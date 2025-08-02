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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->text('question_text_kurdish')->nullable();
            $table->enum('question_type', [
                'multi_choice',
                'puzzle',
                'pattern_recognition',
                'true_false',
                'math'
            ]);
            $table->jsonb('options')->nullable();
            $table->jsonb('options_kurdish')->nullable();
            $table->string('correct_answer');
            $table->string('correct_answer_kurdish')->nullable();
            $table->enum('level', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('seconds')->nullable()->comment('Time allowed for this question in seconds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
