<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Principal;
use App\Models\ReportSubmission;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrincipalPortalController extends Controller
{
    /**
     * Resolve active tenant principal & related principal IDs.
     */
    protected function resolveTenant(Request $request): array
    {
        $tenantPrincipal = $request->attributes->get('tenant_principal')
                        ?? (app()->bound('current_tenant_principal') ? app('current_tenant_principal') : null);

        if (!$tenantPrincipal && Auth::check()) {
            $user = Auth::user();
            if ($user->principals()->exists()) {
                $tenantPrincipal = $user->principals()->first();
            }
        }

        if (!$tenantPrincipal) {
            $subdomain = $request->route('subdomain') ?? $request->query('subdomain');
            if ($subdomain) {
                $tenantPrincipal = Principal::where('subdomain', $subdomain)->where('is_active', true)->first();
            }
        }

        if (!$tenantPrincipal) {
            $tenantPrincipal = Principal::where('is_active', true)->first();
        }

        $tenantPrincipalIds = $request->attributes->get('tenant_principal_ids')
                           ?? (app()->bound('current_tenant_principal_ids') ? app('current_tenant_principal_ids') : []);

        if (empty($tenantPrincipalIds) && $tenantPrincipal) {
            // Find all sister principals sharing the same subdomain/group
            if (!empty($tenantPrincipal->subdomain)) {
                $tenantPrincipalIds = Principal::where('subdomain', $tenantPrincipal->subdomain)
                                               ->where('is_active', true)
                                               ->pluck('id')
                                               ->toArray();
            } else {
                $tenantPrincipalIds = [$tenantPrincipal->id];
            }
        }

        return [$tenantPrincipal, $tenantPrincipalIds];
    }

    /**
     * Portal Executive Dashboard (Sales & Operations Summary)
     */
    public function dashboard(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        // Active report templates for this principal
        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

        // Filters
        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $positionId = $request->query('position_id');
        $employeeId = $request->query('employee_id');
        $locationId = $request->query('location_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $prevStartDate = $startDate->copy()->subMonth()->startOfMonth();
        $prevEndDate = $prevStartDate->copy()->endOfMonth();

        // Timegone calculation (% of days elapsed in selected month)
        $now = Carbon::now();
        if ($year == $now->year && $month == $now->month) {
            $daysInMonth = $startDate->daysInMonth;
            $currentDay = min($now->day, $daysInMonth);
            $timegonePercent = round(($currentDay / $daysInMonth) * 100);
        } elseif ($startDate->isPast()) {
            $timegonePercent = 100;
        } else {
            $timegonePercent = 0;
        }

        // Submissions base query
        $submissionsQuery = ReportSubmission::whereIn('report_submissions.principal_id', $scopedPrincipalIds)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate]);

        if ($employeeId) {
            $submissionsQuery->where('report_submissions.employee_id', $employeeId);
        }
        if ($locationId) {
            $submissionsQuery->where('report_submissions.work_location_id', $locationId);
        }
        if ($positionId) {
            $submissionsQuery->whereHas('employee', function ($q) use ($positionId) {
                $q->where('employees.position_id', $positionId);
            });
        }

        // Total Submissions this month
        $totalSubmissions = (clone $submissionsQuery)->count();

        // Prev month submissions
        $prevSubmissions = ReportSubmission::whereIn('report_submissions.principal_id', $scopedPrincipalIds)
            ->whereBetween('report_submissions.submitted_at', [$prevStartDate, $prevEndDate])
            ->count();

        // Growth calculation
        $growthPercent = $prevSubmissions > 0 
            ? round((($totalSubmissions - $prevSubmissions) / $prevSubmissions) * 100, 1)
            : ($totalSubmissions > 0 ? 100 : 0);

        // Promotor / SPG Metrics
        $employeesQuery = Employee::whereIn('employees.principal_id', $scopedPrincipalIds);
        if ($positionId) {
            $employeesQuery->where('employees.position_id', $positionId);
        }
        $totalEmployees = (clone $employeesQuery)->count();
        $activeEmployees = (clone $employeesQuery)->where('employees.is_active', true)->count();
        $resignedEmployees = (clone $employeesQuery)->where('employees.is_active', false)->count();

        // Active Stores / Locations visited
        $totalStores = (clone $submissionsQuery)->distinct('report_submissions.work_location_id')->count('report_submissions.work_location_id');
        $prevStores = ReportSubmission::whereIn('report_submissions.principal_id', $scopedPrincipalIds)
            ->whereBetween('report_submissions.submitted_at', [$prevStartDate, $prevEndDate])
            ->distinct('report_submissions.work_location_id')
            ->count('report_submissions.work_location_id');

        // Total Sales / Offtake calculation from numeric fields
        $totalSalesVal = DB::table('report_submission_values')
            ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
            ->join('report_form_fields', 'report_submission_values.report_form_field_id', '=', 'report_form_fields.id')
            ->whereIn('report_submissions.principal_id', $scopedPrincipalIds)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('report_form_fields.field_name', 'LIKE', '%nominal%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%total%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%harga%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%sales%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%omset%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%nilai%');
            })
            ->sum('report_submission_values.value_number');

        $prevSalesVal = DB::table('report_submission_values')
            ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
            ->join('report_form_fields', 'report_submission_values.report_form_field_id', '=', 'report_form_fields.id')
            ->whereIn('report_submissions.principal_id', $scopedPrincipalIds)
            ->whereBetween('report_submissions.submitted_at', [$prevStartDate, $prevEndDate])
            ->where(function ($q) {
                $q->where('report_form_fields.field_name', 'LIKE', '%nominal%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%total%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%harga%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%sales%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%omset%')
                  ->orWhere('report_form_fields.field_name', 'LIKE', '%nilai%');
            })
            ->sum('report_submission_values.value_number');

        // Target estimation (example calculation or base target)
        $targetSalesVal = $prevSalesVal > 0 ? $prevSalesVal * 1.15 : ($totalSalesVal > 0 ? $totalSalesVal * 1.2 : 1500000000);
        $achievementPercent = $targetSalesVal > 0 ? min(round(($totalSalesVal / $targetSalesVal) * 100), 100) : 0;
        if ($totalSalesVal == 0 && $totalSubmissions > 0) {
            // Target submission fallback if sales nominal not yet entered
            $targetSubmissions = max($prevSubmissions * 1.1, 100);
            $achievementPercent = min(round(($totalSubmissions / $targetSubmissions) * 100), 100);
        }

        // Daily Submission Chart Data (for ApexCharts)
        $dailyData = (clone $submissionsQuery)
            ->selectRaw('DATE(report_submissions.submitted_at) as date, count(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $chartLabels = [];
        $chartSubmissions = [];
        for ($d = 1; $d <= $startDate->daysInMonth; $d++) {
            $dateStr = $startDate->copy()->day($d)->format('Y-m-d');
            $chartLabels[] = $d . ' ' . $startDate->format('M');
            $chartSubmissions[] = $dailyData[$dateStr] ?? 0;
        }

        // Category breakdown for Donut Chart
        $categoryBreakdown = (clone $submissionsQuery)
            ->join('report_templates', 'report_submissions.report_template_id', '=', 'report_templates.id')
            ->selectRaw('report_templates.category, count(*) as count')
            ->groupBy('report_templates.category')
            ->pluck('count', 'report_templates.category')
            ->toArray();

        // Recent live submissions table
        $recentSubmissions = (clone $submissionsQuery)
            ->with(['template', 'employee', 'workLocation', 'values.formField'])
            ->latest('report_submissions.submitted_at')
            ->paginate(15);

        // Dropdown filter options
        $positions = Position::orderBy('name')->get();
        $employees = Employee::whereIn('employees.principal_id', $scopedPrincipalIds)->orderBy('employees.full_name')->get();
        $workLocations = WorkLocation::whereIn('work_locations.principal_id', $scopedPrincipalIds)->orWhereNull('work_locations.principal_id')->orderBy('work_locations.name')->get();
        $setting = Setting::first();

        return view('portal.dashboard', compact(
            'tenantPrincipal',
            'activeTemplates',
            'month',
            'year',
            'positionId',
            'employeeId',
            'locationId',
            'timegonePercent',
            'totalSubmissions',
            'prevSubmissions',
            'growthPercent',
            'totalEmployees',
            'activeEmployees',
            'resignedEmployees',
            'totalStores',
            'prevStores',
            'totalSalesVal',
            'prevSalesVal',
            'targetSalesVal',
            'achievementPercent',
            'chartLabels',
            'chartSubmissions',
            'categoryBreakdown',
            'recentSubmissions',
            'positions',
            'employees',
            'workLocations',
            'setting'
        ));
    }

    /**
     * Dedicated Report View per Template (e.g. Stock & OOS, Expired Date, Rent Display)
     */
    public function reportDetail(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

        $template = ReportTemplate::where('code', $code)
            ->whereHas('principals', function ($q) use ($scopedPrincipalIds) {
                $q->whereIn('principals.id', $scopedPrincipalIds);
            })
            ->with('fields')
            ->firstOrFail();

        // Filters
        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $search = $request->query('q');
        $employeeId = $request->query('employee_id');
        $locationId = $request->query('location_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with(['employee', 'workLocation', 'values.formField']);

        if ($employeeId) {
            $query->where('report_submissions.employee_id', $employeeId);
        }
        if ($locationId) {
            $query->where('report_submissions.work_location_id', $locationId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($sub) use ($search) {
                    $sub->where('employees.name', 'LIKE', "%{$search}%")
                        ->orWhere('employees.nik', 'LIKE', "%{$search}%");
                })->orWhereHas('workLocation', function ($sub) use ($search) {
                    $sub->where('work_locations.name', 'LIKE', "%{$search}%");
                });
            });
        }

        $submissions = $query->latest('report_submissions.submitted_at')->paginate(20);
        $totalTemplateSubmissions = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->count();

        $uniqueStores = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->distinct('report_submissions.work_location_id')
            ->count('report_submissions.work_location_id');

        $employees = Employee::whereIn('employees.principal_id', $scopedPrincipalIds)->orderBy('employees.name')->get();
        $workLocations = WorkLocation::whereIn('work_locations.principal_id', $scopedPrincipalIds)->orWhereNull('work_locations.principal_id')->orderBy('work_locations.name')->get();
        $setting = Setting::first();

        return view('portal.report_detail', compact(
            'tenantPrincipal',
            'activeTemplates',
            'template',
            'submissions',
            'totalTemplateSubmissions',
            'uniqueStores',
            'month',
            'year',
            'search',
            'employeeId',
            'locationId',
            'employees',
            'workLocations',
            'setting'
        ));
    }

    /**
     * Export Submissions for a Template to CSV / Excel Download
     */
    public function exportReport(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        $template = ReportTemplate::where('code', $code)
            ->whereHas('principals', function ($q) use ($scopedPrincipalIds) {
                $q->whereIn('principals.id', $scopedPrincipalIds);
            })
            ->with('fields')
            ->firstOrFail();

        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $submissions = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with(['employee', 'workLocation', 'values.formField'])
            ->latest('report_submissions.submitted_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"rekap-{$template->code}-{$year}-{$month}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($template, $submissions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // CSV Header Row
            $headerRow = ['ID Laporan', 'Tanggal & Jam', 'NIK', 'Nama Petugas', 'Toko / Lokasi', 'Latitude', 'Longitude', 'Valid Radius'];
            foreach ($template->fields as $field) {
                $headerRow[] = $field->field_label;
            }
            fputcsv($handle, $headerRow);

            // CSV Data Rows
            foreach ($submissions as $sub) {
                $row = [
                    $sub->id,
                    $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : '',
                    $sub->employee?->nik ?? '',
                    $sub->employee?->name ?? '',
                    $sub->workLocation?->name ?? '',
                    $sub->latitude ?? '',
                    $sub->longitude ?? '',
                    $sub->is_within_radius ? 'Ya' : 'Tidak',
                ];

                $valuesMap = $sub->values->keyBy('report_form_field_id');
                foreach ($template->fields as $field) {
                    $val = $valuesMap->get($field->id);
                    $cellVal = '';
                    if ($val) {
                        $cellVal = $val->value_number ?? $val->value_date ?? $val->value_text ?? ($val->file_path ? asset('storage/' . $val->file_path) : '');
                    }
                    $row[] = $cellVal;
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
