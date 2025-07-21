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
        // Add Kurdish language support to questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text_kurdish')->nullable()->after('question_text');
            $table->jsonb('options_kurdish')->nullable()->after('options');
            $table->string('correct_answer_kurdish')->nullable()->after('correct_answer');
        });

        // Add Kurdish language support to competitions table
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('name_kurdish', 100)->nullable()->after('name');
            $table->text('description_kurdish')->nullable()->after('description');
        });

        // Add Kurdish language support to payment methods table

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Kurdish language support from questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_text_kurdish', 'options_kurdish', 'correct_answer_kurdish']);
        });

        // Remove Kurdish language support from competitions table
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['name_kurdish', 'description_kurdish']);
        });

    }
};
