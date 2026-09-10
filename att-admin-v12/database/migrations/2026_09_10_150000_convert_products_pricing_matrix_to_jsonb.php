<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'pricing_matrix')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE products ALTER COLUMN pricing_matrix TYPE jsonb USING (CASE WHEN pricing_matrix IS NULL OR pricing_matrix::text = '' THEN NULL ELSE pricing_matrix::jsonb END)");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'pricing_matrix')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE products ALTER COLUMN pricing_matrix TYPE json USING pricing_matrix::json");
            }
        }
    }
};
