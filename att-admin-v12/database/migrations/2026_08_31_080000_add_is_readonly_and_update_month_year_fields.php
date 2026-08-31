<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom is_readonly pada tabel report_form_fields jika belum ada
        if (Schema::hasTable('report_form_fields')) {
            Schema::table('report_form_fields', function (Blueprint $table) {
                if (!Schema::hasColumn('report_form_fields', 'is_readonly')) {
                    $table->boolean('is_readonly')->default(false)->after('is_required');
                }
            });
        }

        // 2. Update seluruh field tanggal expired yang ada di database agar field_type menjadi 'month_year'
        if (Schema::hasTable('report_form_fields')) {
            DB::table('report_form_fields')
                ->where(function ($query) {
                    $query->where('field_name', 'LIKE', '%expired%')
                          ->orWhere('field_name', 'LIKE', '%kadaluarsa%')
                          ->orWhere('field_label', 'LIKE', '%expired%')
                          ->orWhere('field_label', 'LIKE', '%kadaluarsa%')
                          ->orWhere('field_label', 'LIKE', '%exp date%');
                })
                ->whereIn('field_type', ['date', 'datepicker'])
                ->update([
                    'field_type' => 'month_year',
                    'placeholder' => 'Pilih bulan & tahun expired...',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_form_fields')) {
            Schema::table('report_form_fields', function (Blueprint $table) {
                if (Schema::hasColumn('report_form_fields', 'is_readonly')) {
                    $table->dropColumn('is_readonly');
                }
            });
        }
    }
};
