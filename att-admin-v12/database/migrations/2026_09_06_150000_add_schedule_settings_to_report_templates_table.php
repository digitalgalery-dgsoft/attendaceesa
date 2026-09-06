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
        Schema::table('report_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('report_templates', 'schedule_type')) {
                $table->string('schedule_type')->default('daily')->after('category');
            }
            if (!Schema::hasColumn('report_templates', 'target_count')) {
                $table->integer('target_count')->default(1)->after('schedule_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            if (Schema::hasColumn('report_templates', 'target_count')) {
                $table->dropColumn('target_count');
            }
            if (Schema::hasColumn('report_templates', 'schedule_type')) {
                $table->dropColumn('schedule_type');
            }
        });
    }
};
