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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'min_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('min_stock')->default(0)->after('price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'min_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('min_stock');
            });
        }
    }
};
