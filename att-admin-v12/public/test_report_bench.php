<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use App\Models\ReportTemplate;
use App\Models\WorkLocation;
use Carbon\Carbon;

echo "=== BENCHMARK REPORT DETAIL ===\n";

$t0 = microtime(true);
$code = 'RPT-DULUX-OOS-SSO';
$template = ReportTemplate::where('code', $code)->with(['fields' => fn($q)=>$q->orderBy('order_index')])->first();
echo "1. Template found (ID: {$template->id}) in: " . round(microtime(true) - $t0, 4) . "s\n";

$t1 = microtime(true);
$latestSubDate = DB::table('report_submissions')->where('report_template_id', $template->id)->max('submitted_at');
echo "2. Latest sub date ({$latestSubDate}) in: " . round(microtime(true) - $t1, 4) . "s\n";

$c = Carbon::parse($latestSubDate);
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
echo "3. Stats count in: " . round(microtime(true) - $t2, 4) . "s (Total: {$row->total_count}, Stores: {$row->store_count})\n";

$t3 = microtime(true);
$items = \App\Models\ReportSubmission::where('report_template_id', $template->id)
    ->whereBetween('submitted_at', [$startDate, $endDate])
    ->with(['employee.branch', 'workLocation.branch', 'values.formField'])
    ->latest('submitted_at')
    ->limit(20)
    ->get();
echo "4. Items (20 rows with relations) in: " . round(microtime(true) - $t3, 4) . "s\n";

$t4 = microtime(true);
$dashboardConfig = $template->resolved_dashboard_config;
$widgets = $dashboardConfig['widgets'] ?? [];
echo "5. Resolved widgets: " . count($widgets) . " in: " . round(microtime(true) - $t4, 4) . "s\n";

foreach ($widgets as $idx => $w) {
    $tw = microtime(true);
    $type = $w['type'] ?? '';
    $dim = $w['dimension_field'] ?? '';
    $metric = $w['metric_field'] ?? '';
    echo "  -> Widget #{$idx} ({$w['id']}, {$type}, dim: {$dim}, metric: {$metric})... ";
    if ($type === 'kpi_card') {
        if ($metric === '_submission' || $dim === '_total_count' || empty($metric)) {
            $val = $row->total_count;
        } elseif ($metric === '_unique_store') {
            $val = $row->store_count;
        } else {
            $val = DB::table('report_submission_values')
                ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
                ->where('report_submissions.report_template_id', $template->id)
                ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
                ->where('report_submission_values.field_name', $metric)
                ->count();
        }
        echo "val: {$val} in: " . round(microtime(true) - $tw, 4) . "s\n";
    } elseif (in_array($type, ['bar_chart', 'donut_chart', 'pie_chart', 'line_chart'])) {
        if ($dim === '_submitted_date') {
            $res = DB::table('report_submissions')
                ->where('report_template_id', $template->id)
                ->whereBetween('submitted_at', [$startDate, $endDate])
                ->selectRaw("TO_CHAR(submitted_at, 'YYYY-MM-DD') as d, count(*) as c")
                ->groupBy('d')->get();
            echo "rows: " . count($res) . " in: " . round(microtime(true) - $tw, 4) . "s\n";
        } else {
            $res = DB::table('report_submission_values')
                ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
                ->where('report_submissions.report_template_id', $template->id)
                ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
                ->where('report_submission_values.field_name', $dim)
                ->selectRaw('report_submission_values.value_text as label, count(*) as total')
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
            echo "rows: " . count($res) . " in: " . round(microtime(true) - $tw, 4) . "s\n";
        }
    }
}

echo "TOTAL TIME: " . round(microtime(true) - $t0, 4) . "s\n";
