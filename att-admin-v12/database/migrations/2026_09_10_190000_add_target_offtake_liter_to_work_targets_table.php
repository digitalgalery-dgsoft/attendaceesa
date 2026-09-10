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
        Schema::table('work_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('work_targets', 'target_offtake_liter')) {
                $table->decimal('target_offtake_liter', 12, 2)->nullable()->default(0)->after('target_hk')->comment('Target Offtake Penjualan dalam Liter per bulan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_targets', function (Blueprint $table) {
            if (Schema::hasColumn('work_targets', 'target_offtake_liter')) {
                $table->dropColumn('target_offtake_liter');
            }
        });
    }
};
