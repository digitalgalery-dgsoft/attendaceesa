<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $today = '2026-09-07';

        // 1. Ambil ID semua report_submissions yang disubmit / dibuat pada 2026-09-07
        $subIds = DB::table('report_submissions')
            ->whereDate('submitted_at', $today)
            ->orWhereDate('created_at', $today)
            ->pluck('id');

        if ($subIds->isNotEmpty()) {
            // Hapus child report_submission_values terlebih dahulu
            DB::table('report_submission_values')
                ->whereIn('report_submission_id', $subIds)
                ->delete();

            // Hapus parent report_submissions
            DB::table('report_submissions')
                ->whereIn('id', $subIds)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // irreversible test cleanup
    }
};
