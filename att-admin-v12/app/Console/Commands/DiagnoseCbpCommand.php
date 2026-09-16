<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportTemplate;
use App\Models\ReportSubmission;
use App\Http\Controllers\Portal\PrincipalPortalController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiagnoseCbpCommand extends Command
{
    protected $signature = 'dulux:diagnose-cbp';
    protected $description = 'Diagnose CBP report memory and query performance';

    public function handle()
    {
        $this->info("Memory start: " . round(memory_get_usage(true)/1024/1024, 2) . " MB");

        $template = ReportTemplate::where('code', 'RPT-DULUX-CBP-PRICING')->first();
        if (!$template) {
            $this->error("Template RPT-DULUX-CBP-PRICING not found!");
            return;
        }

        $this->info("Template found: ID={$template->id}, code={$template->code}");

        $startDate = Carbon::create(2026, 1, 1)->startOfMonth();
        $endDate = Carbon::create(2026, 9, 30)->endOfMonth();

        $subCount = ReportSubmission::where('report_template_id', $template->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->count();
        $this->info("ReportSubmission count for template in Jan-Sep 2026: {$subCount}");

        $totalSubCount = ReportSubmission::where('report_template_id', $template->id)->count();
        $this->info("Total ReportSubmission count for template (all time): {$totalSubCount}");

        $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
        $this->info("SQLite file exists: " . (file_exists($sqlitePath) ? 'YES (' . round(filesize($sqlitePath)/1024/1024, 2) . ' MB)' : 'NO'));

        $this->info("Calling calculateCbpDashboardData(1..9, 2026)...");
        $controller = app(PrincipalPortalController::class);

        $t0 = microtime(true);
        $memBefore = memory_get_usage(true);
        try {
            $res = $controller->calculateCbpDashboardData(
                $template,
                1, 2026, 9, 2026,
                null, null, null, null,
                1, 50
            );
            $timeTaken = round(microtime(true) - $t0, 3);
            $memAfter = memory_get_usage(true);
            $peakMem = memory_get_peak_usage(true);
            $this->info("SUCCESS! calculateCbpDashboardData completed in {$timeTaken}s");
            $this->info("Memory before: " . round($memBefore/1024/1024, 2) . " MB, after: " . round($memAfter/1024/1024, 2) . " MB, peak: " . round($peakMem/1024/1024, 2) . " MB");
            $this->info("Total records in kpis: " . ($res['kpis']['total_records'] ?? 'N/A'));
            $this->info("Raw data rows: " . count($res['raw_data'] ?? []));
        } catch (\Throwable $e) {
            $this->error("ERROR in calculateCbpDashboardData: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            $this->error($e->getTraceAsString());
        }

        $this->info("Testing reportDetail simulation...");
        $req = Request::create('/portal/report/RPT-DULUX-CBP-PRICING?p=18&start_month=1&start_year=2026&end_month=9&end_year=2026', 'GET');
        $t0 = microtime(true);
        try {
            $response = $controller->reportDetail($req, 'RPT-DULUX-CBP-PRICING');
            $timeTaken = round(microtime(true) - $t0, 3);
            $this->info("SUCCESS! reportDetail completed in {$timeTaken}s with status " . (is_object($response) && method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 'view'));
        } catch (\Throwable $e) {
            $this->error("ERROR in reportDetail: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            $this->error($e->getTraceAsString());
        }
    }
}
