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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('title_arabic')->nullable()->after('title_kurdish');
            $table->text('message_arabic')->nullable()->after('message_kurdish');
            $table->string('title_kurmanji')->nullable()->after('title_arabic');
            $table->text('message_kurmanji')->nullable()->after('message_arabic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['title_arabic', 'message_arabic', 'title_kurmanji', 'message_kurmanji']);
        });
    }
};
