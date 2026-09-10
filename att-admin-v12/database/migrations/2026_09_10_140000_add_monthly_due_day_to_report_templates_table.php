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
        if (Schema::hasTable('report_templates')) {
            Schema::table('report_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('report_templates', 'monthly_due_day')) {
                    $table->unsignedTinyInteger('monthly_due_day')->nullable()->after('target_count')
                        ->comment('Maksimal tanggal harus lapor dalam 1 bulan (1-31). Sebelum tanggal ini, laporan bisa dilewati.');
                }
            });

            // Set default schedule_type = 'monthly' dan monthly_due_day = 25 untuk Laporan Stock End Bulanan
            DB::table('report_templates')
                ->where('code', 'RPT-DULUX-STOCK-END')
                ->orWhere('code', 'LIKE', '%STOCK-END%')
                ->orWhere('title', 'LIKE', '%Stock Opname Bulanan%')
                ->update([
                    'schedule_type' => 'monthly',
                    'monthly_due_day' => 25,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_templates')) {
            Schema::table('report_templates', function (Blueprint $table) {
                if (Schema::hasColumn('report_templates', 'monthly_due_day')) {
                    $table->dropColumn('monthly_due_day');
                }
            });
        }
    }
};
