<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('name_arabic', 100)->nullable()->after('name');
            $table->text('description_arabic')->nullable()->after('description');
            $table->string('name_kurmanji', 100)->nullable()->after('name_kurdish');
            $table->text('description_kurmanji')->nullable()->after('description_kurdish');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'name_arabic',
                'description_arabic',
                'name_kurmanji',
                'description_kurmanji',
            ]);
        });
    }
};
