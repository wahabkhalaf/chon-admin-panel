<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // 'ios' or 'android'
            $table->string('version'); // e.g., '1.1.0'
            $table->integer('build_number'); // e.g., 10
            $table->string('app_store_url')->nullable(); // App Store/Play Store URL
            $table->text('release_notes')->nullable(); // What's new in this version
            $table->boolean('is_force_update')->default(false); // Force users to update
            $table->boolean('is_active')->default(true); // Enable/disable this version
            $table->timestamp('released_at')->nullable(); // When this version was released
            $table->timestamps();
            
            // Ensure unique platform + version combinations
            $table->unique(['platform', 'version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_versions');
    }
};
