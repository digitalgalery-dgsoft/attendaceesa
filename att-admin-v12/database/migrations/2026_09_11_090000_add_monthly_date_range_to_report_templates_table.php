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
                if (!Schema::hasColumn('report_templates', 'monthly_start_day')) {
                    $table->unsignedTinyInteger('monthly_start_day')->nullable()->after('target_count')
                        ->comment('Awal rentang tanggal aktif pelaporan bulanan (1-31)');
                }
                if (!Schema::hasColumn('report_templates', 'monthly_end_day')) {
                    $table->unsignedTinyInteger('monthly_end_day')->nullable()->after('monthly_start_day')
                        ->comment('Akhir rentang tanggal aktif pelaporan bulanan (1-31)');
                }
            });

            // Set default rentang tanggal: 24 - 30 untuk Laporan Stock End Bulanan Dulux
            DB::table('report_templates')
                ->where('code', 'RPT-DULUX-STOCK-END')
                ->orWhere('code', 'LIKE', '%STOCK-END%')
                ->orWhere('title', 'LIKE', '%Stock Opname Bulanan%')
                ->update([
                    'schedule_type' => 'monthly',
                    'monthly_start_day' => 24,
                    'monthly_end_day' => 30,
                    'monthly_due_day' => 30,
                ]);

            // Untuk template monthly lainnya yang sudah memiliki monthly_due_day
            $templates = DB::table('report_templates')
                ->where('schedule_type', 'monthly')
                ->whereNull('monthly_start_day')
                ->get();

            foreach ($templates as $tpl) {
                $due = $tpl->monthly_due_day ?? 30;
                $start = max(1, $due - 6);
                DB::table('report_templates')
                    ->where('id', $tpl->id)
                    ->update([
                        'monthly_start_day' => $start,
                        'monthly_end_day' => $due,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_templates')) {
            Schema::table('report_templates', function (Blueprint $table) {
                if (Schema::hasColumn('report_templates', 'monthly_start_day')) {
                    $table->dropColumn('monthly_start_day');
                }
                if (Schema::hasColumn('report_templates', 'monthly_end_day')) {
                    $table->dropColumn('monthly_end_day');
                }
            });
        }
    }
};
