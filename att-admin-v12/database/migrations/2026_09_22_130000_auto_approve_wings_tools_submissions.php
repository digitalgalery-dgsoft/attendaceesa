<?php

use App\Models\ReportSubmission;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Auto-approve all existing submissions for Wings MBR Tools
        $templates = ReportTemplate::where('code', 'RPT-WINGS-MBR-TOOLS-01')
            ->orWhere('code', 'LIKE', '%MBR-TOOLS%')
            ->orWhere('code', 'LIKE', '%WINGS-TOOLS%')
            ->orWhere('code', 'LIKE', '%WINGS-MBR%')
            ->pluck('id');

        if ($templates->isNotEmpty()) {
            DB::table('report_submissions')
                ->whereIn('report_template_id', $templates)
                ->whereIn('status', ['pending', 'submitted'])
                ->update([
                    'status' => 'approved',
                    'verified_at' => now(),
                    'verification_notes' => 'Otomatis diterima sistem (tanpa approval)',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for approved status
    }
};
