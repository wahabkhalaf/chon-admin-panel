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
            $table->string('platform'); 
            $table->string('version');  
            $table->integer('build_number'); 
            $table->string('app_store_url')->nullable(); 
            $table->text('release_notes')->nullable(); 
            $table->boolean('is_force_update')->default(false); 
            $table->boolean('is_active')->default(true); 
            $table->timestamp('released_at')->nullable(); 
            $table->timestamps();
            $table->unique(['platform', 'version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_versions');
    }
};
