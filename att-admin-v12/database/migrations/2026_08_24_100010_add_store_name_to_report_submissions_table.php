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
        Schema::table('report_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('report_submissions', 'store_name')) {
                $table->string('store_name')->nullable()->after('work_location_id');
            }
            if (!Schema::hasColumn('report_submissions', 'address')) {
                $table->text('address')->nullable()->after('store_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('report_submissions', 'store_name')) {
                $table->dropColumn('store_name');
            }
            if (Schema::hasColumn('report_submissions', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
