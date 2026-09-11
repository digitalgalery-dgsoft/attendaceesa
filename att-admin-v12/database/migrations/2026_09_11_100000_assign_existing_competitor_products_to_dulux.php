<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assigns any existing competitor products with null principal_id to the Dulux / ICI Paints principal.
     */
    public function up(): void
    {
        if (Schema::hasTable('competitor_products') && Schema::hasTable('principals')) {
            $dulux = DB::table('principals')
                ->where('code', 'LIKE', '%DULUX%')
                ->orWhere('code', 'LIKE', '%ICI%')
                ->orWhere('name', 'LIKE', '%DULUX%')
                ->orWhere('name', 'LIKE', '%ICI%')
                ->orWhere('subdomain', 'dulux')
                ->first();

            if ($dulux) {
                DB::table('competitor_products')
                    ->whereNull('principal_id')
                    ->update(['principal_id' => $dulux->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data assignment
    }
};
