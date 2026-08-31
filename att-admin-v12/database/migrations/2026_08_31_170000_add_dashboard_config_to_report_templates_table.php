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
        if (Schema::hasTable('report_templates') && !Schema::hasColumn('report_templates', 'dashboard_config')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->json('dashboard_config')->nullable()->after('report_days');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_templates') && Schema::hasColumn('report_templates', 'dashboard_config')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->dropColumn('dashboard_config');
            });
        }
    }
};
