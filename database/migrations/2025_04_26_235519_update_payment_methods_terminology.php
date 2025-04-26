<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We won't physically rename the columns to maintain backward compatibility,
        // but we'll update the comments to reflect the new terminology

        DB::statement('COMMENT ON COLUMN payment_methods.supports_deposit IS \'Supports competition entry payments\'');
        DB::statement('COMMENT ON COLUMN payment_methods.supports_withdrawal IS \'Supports prize payouts\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the comments to their original meaning
        DB::statement('COMMENT ON COLUMN payment_methods.supports_deposit IS NULL');
        DB::statement('COMMENT ON COLUMN payment_methods.supports_withdrawal IS NULL');
    }
};
