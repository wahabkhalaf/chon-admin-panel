<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text_arabic')->nullable()->after('question_text_kurdish');
            $table->jsonb('options_arabic')->nullable()->after('options_kurdish');
            $table->string('correct_answer_arabic')->nullable()->after('correct_answer_kurdish');

            $table->text('question_text_kurmanji')->nullable()->after('question_text_arabic');
            $table->jsonb('options_kurmanji')->nullable()->after('options_arabic');
            $table->string('correct_answer_kurmanji')->nullable()->after('correct_answer_arabic');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'question_text_arabic',
                'options_arabic',
                'correct_answer_arabic',
                'question_text_kurmanji',
                'options_kurmanji',
                'correct_answer_kurmanji',
            ]);
        });
    }
};
