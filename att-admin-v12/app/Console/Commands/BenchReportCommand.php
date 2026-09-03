<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ReportTemplate;
use Carbon\Carbon;

class BenchReportCommand extends Command
{
    protected $signature = 'report:bench {code=RPT-DULUX-OOS-SSO}';
    protected $description = 'Benchmark report execution';

    public function handle(): int
    {
        $code = $this->argument('code');
        $this->info("=== BENCHMARK REPORT DETAIL FOR: {$code} ===");

        $t0 = microtime(true);
        $template = ReportTemplate::where('code', $code)->with(['fields' => fn($q)=>$q->orderBy('order_index')])->first();
        if (!$template) {
            $this->error("Template {$code} not found!");
            return 1;
        }
        $this->line("1. Template found (ID: {$template->id}) in: " . round(microtime(true) - $t0, 4) . "s");

        $t1 = microtime(true);
        $latestSubDate = DB::table('report_submissions')->where('report_template_id', $template->id)->max('submitted_at');
        $this->line("2. Latest sub date ({$latestSubDate}) in: " . round(microtime(true) - $t1, 4) . "s");

        $c = $latestSubDate ? Carbon::parse($latestSubDate) : Carbon::now();
        $startDate = Carbon::createFromDate($c->year, $c->month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($c->year, $c->month, 1)->endOfMonth();

        $t2 = microtime(true);
        $row = DB::table('report_submissions')
            ->where('report_template_id', $template->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_count,
                COUNT(DISTINCT work_location_id) as store_count,
                COUNT(DISTINCT employee_id) as emp_count
            ')->first();
        $this->line("3. Stats count in: " . round(microtime(true) - $t2, 4) . "s (Total: {$row->total_count}, Stores: {$row->store_count})");

        $t3 = microtime(true);
        $items = \App\Models\ReportSubmission::where('report_template_id', $template->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->with(['employee.branch', 'workLocation.branch', 'values.formField'])
            ->latest('submitted_at')
            ->limit(20)
            ->get();
        $this->line("4. Items (20 rows with relations) in: " . round(microtime(true) - $t3, 4) . "s");

        $tSubIds = microtime(true);
        $submissionIds = DB::table('report_submissions')
            ->where('report_template_id', $template->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->pluck('id')->toArray();
        $this->line("4b. Plucked " . count($submissionIds) . " submission IDs in: " . round(microtime(true) - $tSubIds, 4) . "s");

        $t4 = microtime(true);
        $dashboardConfig = $template->resolved_dashboard_config;
        $widgets = $dashboardConfig['widgets'] ?? [];
        $this->line("5. Resolved widgets: " . count($widgets) . " in: " . round(microtime(true) - $t4, 4) . "s");

        foreach ($widgets as $idx => $w) {
            $tw = microtime(true);
            $type = $w['type'] ?? '';
            $dim = $w['dimension_field'] ?? '';
            $metric = $w['metric_field'] ?? '';
            $this->output->write("  -> Widget #{$idx} ({$w['id']}, {$type}, dim: {$dim}, metric: {$metric})... ");
            if ($type === 'kpi_card') {
                if ($metric === '_submission' || $dim === '_total_count' || empty($metric)) {
                    $val = $row->total_count;
                } elseif ($metric === '_unique_store') {
                    $val = $row->store_count;
                } else {
                    $val = empty($submissionIds) ? 0 : DB::table('report_submission_values')
                        ->where('field_name', $metric)
                        ->whereIn('report_submission_id', $submissionIds)
                        ->count();
                }
                $this->line("val: {$val} in: " . round(microtime(true) - $tw, 4) . "s");
            } elseif (in_array($type, ['bar_chart', 'donut_chart', 'pie_chart', 'line_chart'])) {
                if ($dim === '_submitted_date') {
                    $res = DB::table('report_submissions')
                        ->where('report_template_id', $template->id)
                        ->whereBetween('submitted_at', [$startDate, $endDate])
                        ->selectRaw("TO_CHAR(submitted_at, 'YYYY-MM-DD') as d, count(*) as c")
                        ->groupBy('d')->get();
                    $this->line("rows: " . count($res) . " in: " . round(microtime(true) - $tw, 4) . "s");
                } else {
                    $res = empty($submissionIds) ? [] : DB::table('report_submission_values')
                        ->where('field_name', $dim)
                        ->whereIn('report_submission_id', $submissionIds)
                        ->selectRaw('value_text as label, count(*) as total')
                        ->groupBy('label')
                        ->orderByDesc('total')
                        ->limit(10)
                        ->get();
                    $this->line("rows: " . count($res) . " in: " . round(microtime(true) - $tw, 4) . "s");
                }
            }
        }

        $this->info("TOTAL TIME: " . round(microtime(true) - $t0, 4) . "s");
        return 0;
    }
}
