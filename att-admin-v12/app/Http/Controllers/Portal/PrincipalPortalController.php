<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ExtraHour;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportSubmission;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\VisitReport;
use App\Models\WorkLocation;
use App\Models\WorkingGroup;
use App\Models\WorkingGroupMember;
use App\Models\WorkingGroupRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PrincipalPortalController extends Controller
{
    /**
     * Resolve active tenant principal & related principal IDs.
     */
    protected function resolveTenant(Request $request): array
    {
        $tenantPrincipal = $request->attributes->get('tenant_principal')
                        ?? (app()->bound('current_tenant_principal') ? app('current_tenant_principal') : null);

        $requestedId = $request->query('p') ?? $request->query('principal_id');

        if ($requestedId) {
            $found = Principal::where('id', (int) $requestedId)->where('is_active', true)->first();
            if ($found) {
                $tenantPrincipal = $found;
            }
        }

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

        // Resolve all sibling / tenant principals
        $tenantPrincipalsAll = $request->attributes->get('tenant_principals_all')
                            ?? (app()->bound('current_tenant_principals_all') ? app('current_tenant_principals_all') : null);

        if (!$tenantPrincipalsAll || $tenantPrincipalsAll->isEmpty()) {
            if ($tenantPrincipal && !empty($tenantPrincipal->subdomain)) {
                $tenantPrincipalsAll = Principal::where('subdomain', $tenantPrincipal->subdomain)
                                                ->where('is_active', true)
                                                ->orderBy('id')
                                                ->get();
            } elseif (Auth::check() && Auth::user()->principals()->exists()) {
                $tenantPrincipalsAll = Auth::user()->principals()->where('is_active', true)->orderBy('id')->get();
            } else {
                $tenantPrincipalsAll = collect($tenantPrincipal ? [$tenantPrincipal] : []);
            }
        }

        // Strictly scope data (Employees, Products, Submissions, Templates) to active entity ids
        if ($tenantPrincipal && !empty($tenantPrincipal->subdomain)) {
            $tenantPrincipalIds = Principal::where('subdomain', $tenantPrincipal->subdomain)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        } elseif ($tenantPrincipal) {
            $tenantPrincipalIds = Principal::where('name', $tenantPrincipal->name)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        } else {
            $tenantPrincipalIds = [];
        }

        if (empty($tenantPrincipalIds) && $tenantPrincipal) {
            $tenantPrincipalIds = [$tenantPrincipal->id];
        }

        if ($tenantPrincipal) {
            $subdomain = strtolower($tenantPrincipal->subdomain ?? '');
            $name = strtolower($tenantPrincipal->name ?? '');
            if ($subdomain === 'dulux' || str_contains($name, 'ici') || str_contains($name, 'dulux')) {
                try {
                    ReportTemplate::syncDuluxMergedStockEnd();
                } catch (\Throwable $e) {}
            }
        }

        return [$tenantPrincipal, $tenantPrincipalIds, $tenantPrincipalsAll];
    }

    /**
     * Helper to retrieve active report templates for tenant principal
     */
    protected function getActiveTemplates(array $scopedPrincipalIds, ?Principal $tenantPrincipal = null)
    {
        return $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.is_active', true)
            ->with('fields')
            ->orderBy('report_templates.id')
            ->get();
    }

    /**
     * Base query for report templates belonging to the scoped principals
     */
    protected function getTemplateBaseQuery(array $scopedPrincipalIds, ?Principal $tenantPrincipal = null)
    {
        return ReportTemplate::where(function ($q) use ($scopedPrincipalIds, $tenantPrincipal) {
            if ($tenantPrincipal) {
                $subdomain = strtolower($tenantPrincipal->subdomain ?? '');
                $name = strtolower($tenantPrincipal->name ?? '');

                if ($subdomain === 'dulux' || str_contains($name, 'ici') || str_contains($name, 'dulux') || str_contains($name, 'akzonobel')) {
                    $q->where(function ($sub) use ($scopedPrincipalIds) {
                        $sub->whereHas('principals', fn($p) => $p->whereIn('principals.id', $scopedPrincipalIds))
                            ->orWhereIn('report_templates.principal_id', $scopedPrincipalIds)
                            ->orWhere('report_templates.code', 'LIKE', '%DULUX%')
                            ->orWhere('report_templates.code', 'LIKE', '%ICI%')
                            ->orWhere('report_templates.title', 'LIKE', '%Dulux%')
                            ->orWhere('report_templates.title', 'LIKE', '%ICI%');
                    })
                    ->where('report_templates.code', 'NOT LIKE', '%MAMASUKA%')
                    ->where('report_templates.code', 'NOT LIKE', '%DAESANG%')
                    ->where('report_templates.code', 'NOT LIKE', '%WINGS%')
                    ->where('report_templates.code', 'NOT LIKE', '%FONTERRA%')
                    ->where('report_templates.code', 'NOT LIKE', '%ANLENE%')
                    ->where('report_templates.code', 'NOT LIKE', '%SIDO%')
                    ->where('report_templates.title', 'NOT LIKE', '%Mamasuka%');
                    return;
                } elseif ($subdomain === 'wings' || str_contains($name, 'wings') || str_contains($name, 'sayap')) {
                    $q->where(function ($sub) use ($scopedPrincipalIds) {
                        $sub->whereHas('principals', fn($p) => $p->whereIn('principals.id', $scopedPrincipalIds))
                            ->orWhereIn('report_templates.principal_id', $scopedPrincipalIds)
                            ->orWhere('report_templates.code', 'LIKE', '%WINGS%')
                            ->orWhere('report_templates.title', 'LIKE', '%Wings%');
                    })
                    ->where('report_templates.code', 'NOT LIKE', '%DULUX%')
                    ->where('report_templates.code', 'NOT LIKE', '%ICI%')
                    ->where('report_templates.code', 'NOT LIKE', '%MAMASUKA%');
                    return;
                } elseif ($subdomain === 'fonterra' || str_contains($name, 'fonterra') || str_contains($name, 'anlene')) {
                    $q->where(function ($sub) use ($scopedPrincipalIds) {
                        $sub->whereHas('principals', fn($p) => $p->whereIn('principals.id', $scopedPrincipalIds))
                            ->orWhereIn('report_templates.principal_id', $scopedPrincipalIds)
                            ->orWhere('report_templates.code', 'LIKE', '%FONTERRA%')
                            ->orWhere('report_templates.code', 'LIKE', '%ANLENE%')
                            ->orWhere('report_templates.title', 'LIKE', '%Fonterra%');
                    })
                    ->where('report_templates.code', 'NOT LIKE', '%DULUX%')
                    ->where('report_templates.code', 'NOT LIKE', '%ICI%');
                    return;
                } elseif ($subdomain === 'mamasuka' || str_contains($name, 'mamasuka') || str_contains($name, 'daesang')) {
                    $q->where(function ($sub) use ($scopedPrincipalIds) {
                        $sub->whereHas('principals', fn($p) => $p->whereIn('principals.id', $scopedPrincipalIds))
                            ->orWhereIn('report_templates.principal_id', $scopedPrincipalIds)
                            ->orWhere('report_templates.code', 'LIKE', '%MAMASUKA%')
                            ->orWhere('report_templates.code', 'LIKE', '%DAESANG%')
                            ->orWhere('report_templates.title', 'LIKE', '%Mamasuka%');
                    })
                    ->where('report_templates.code', 'NOT LIKE', '%DULUX%')
                    ->where('report_templates.code', 'NOT LIKE', '%ICI%');
                    return;
                } elseif ($subdomain === 'sidomuncul' || str_contains($name, 'sido') || str_contains($name, 'tolak angin')) {
                    $q->where(function ($sub) use ($scopedPrincipalIds) {
                        $sub->whereHas('principals', fn($p) => $p->whereIn('principals.id', $scopedPrincipalIds))
                            ->orWhereIn('report_templates.principal_id', $scopedPrincipalIds)
                            ->orWhere('report_templates.code', 'LIKE', '%SIDO%')
                            ->orWhere('report_templates.title', 'LIKE', '%Sido%');
                    })
                    ->where('report_templates.code', 'NOT LIKE', '%DULUX%')
                    ->where('report_templates.code', 'NOT LIKE', '%ICI%');
                    return;
                }
            }

            $q->whereHas('principals', function ($sub) use ($scopedPrincipalIds) {
                $sub->whereIn('principals.id', $scopedPrincipalIds);
            })->orWhereIn('report_templates.principal_id', $scopedPrincipalIds);
        });
    }

    /**
     * Portal Executive Dashboard (Sales & Operations Summary)
     */
    public function dashboard(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        // Active report templates for this principal
        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

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

        // Real Submission counts this month
        $totalSubmissions = (clone $submissionsQuery)->count();
        $approvedSubmissions = (clone $submissionsQuery)->whereIn('status', ['approved', 'verified'])->count();
        $pendingSubmissions = (clone $submissionsQuery)->whereIn('status', ['pending', 'submitted'])->count();
        $rejectedSubmissions = (clone $submissionsQuery)->where('status', 'rejected')->count();

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

        // Grouped employees by Branch/Area for searchable dropdown
        $employees = Employee::whereIn('employees.principal_id', $scopedPrincipalIds)
            ->with(['branch', 'position'])
            ->orderBy('employees.full_name')
            ->get();

        $groupedEmployees = $employees->groupBy(function($emp) {
            return ($emp->branch && $emp->branch->name) ? $emp->branch->name : 'Pusat / Seluruh Area';
        });

        $workLocations = WorkLocation::whereIn('work_locations.principal_id', $scopedPrincipalIds)->orWhereNull('work_locations.principal_id')->orderBy('work_locations.name')->get();
        $setting = Setting::first();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';

        // Total Products (SKU)
        $totalProducts = Product::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->count();

        return view('portal.dashboard', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'month',
            'year',
            'employeeId',
            'locationId',
            'timegonePercent',
            'totalSubmissions',
            'approvedSubmissions',
            'pendingSubmissions',
            'rejectedSubmissions',
            'prevSubmissions',
            'growthPercent',
            'totalEmployees',
            'activeEmployees',
            'resignedEmployees',
            'totalStores',
            'prevStores',
            'totalSalesVal',
            'prevSalesVal',
            'chartLabels',
            'chartSubmissions',
            'categoryBreakdown',
            'recentSubmissions',
            'employees',
            'groupedEmployees',
            'workLocations',
            'totalProducts',
            'setting'
        ));
    }

    /**
     * Dedicated Report View per Template (e.g. Stock & OOS, Expired Date, Rent Display)
     */
    public function reportDetail(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);
        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->with(['fields' => function ($q) {
                $q->orderBy('order_index');
            }])
            ->firstOrFail();

        // Filters: Rentang Bulan Awal s/d Bulan Akhir
        $startMonth = (int) ($request->query('start_month') ?? $request->query('month') ?? Carbon::now()->month);
        $startYear  = (int) ($request->query('start_year') ?? $request->query('year') ?? Carbon::now()->year);
        $endMonth   = (int) ($request->query('end_month') ?? $startMonth);
        $endYear    = (int) ($request->query('end_year') ?? $startYear);

        $startDate  = Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
        $endDate    = Carbon::createFromDate($endYear, $endMonth, 1)->endOfMonth();

        $selectedRegion     = $request->query('region');
        $selectedAreaId     = $request->query('area_id') ?? $request->query('branch_id');
        $selectedLocationId = $request->query('location_id') ?? $request->query('store_id');
        $search             = $request->query('q');

        $query = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with(['employee.branch', 'workLocation.branch', 'values.formField']);

        if ($selectedRegion) {
            $query->where(function ($q) use ($selectedRegion) {
                $q->whereHas('workLocation', fn($w) => $w->where('region', $selectedRegion))
                  ->orWhereHas('employee.branch', fn($b) => $b->where('region', $selectedRegion));
            });
        }
        if ($selectedAreaId) {
            $query->where(function ($q) use ($selectedAreaId) {
                $q->whereHas('workLocation', fn($w) => $w->where('branch_id', $selectedAreaId))
                  ->orWhereHas('employee', fn($e) => $e->where('branch_id', $selectedAreaId));
            });
        }
        if ($selectedLocationId) {
            $query->where('report_submissions.work_location_id', $selectedLocationId);
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

        $totalTemplateSubmissions = (clone $query)->count();
        $uniqueStores = (clone $query)->distinct('report_submissions.work_location_id')->count('report_submissions.work_location_id');
        $uniqueEmployees = (clone $query)->distinct('report_submissions.employee_id')->count('report_submissions.employee_id');

        $submissions = $query->latest('report_submissions.submitted_at')->paginate(20);

        // Hanya load collection ke memory jika dataset wajar (<= 1500 rows) agar tidak kehabisan RAM pada dataset besar
        $allFilteredSubmissions = $totalTemplateSubmissions <= 1500 
            ? (clone $query)->get() 
            : collect();

        // Dynamic Dashboard Configuration & Widget Calculation Engine (Concept Odoo Studio)
        $dashboardConfig = $template->resolved_dashboard_config;
        $widgets = $dashboardConfig['widgets'] ?? [];
        $widgetResults = [];

        foreach ($widgets as $w) {
            $wId = $w['id'] ?? uniqid('w_');
            $type = $w['type'] ?? 'kpi_card';
            $dim = $w['dimension_field'] ?? null;
            $metric = $w['metric_field'] ?? null;
            $agg = strtoupper($w['aggregation'] ?? 'COUNT');

            if ($type === 'kpi_card') {
                $val = 0;
                if ($metric === '_submission' || $dim === '_total_count' || empty($metric)) {
                    $val = $totalTemplateSubmissions;
                } elseif ($metric === '_unique_store' || $dim === 'work_location_id' || $metric === 'work_location_id') {
                    $val = $uniqueStores;
                } elseif ($metric === '_unique_employee' || $dim === 'employee_id' || $metric === 'employee_id') {
                    $val = $uniqueEmployees;
                } else {
                    if ($totalTemplateSubmissions > 1500) {
                        // High performance DB aggregation
                        $valQuery = DB::table('report_submission_values')
                            ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
                            ->where('report_submissions.report_template_id', $template->id)
                            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
                            ->where('report_submission_values.field_name', $metric);

                        if ($selectedRegion) {
                            $valQuery->join('work_locations', 'report_submissions.work_location_id', '=', 'work_locations.id')
                                     ->where('work_locations.region', $selectedRegion);
                        }
                        if ($selectedAreaId) {
                            $valQuery->where('report_submissions.work_location_id', function($subQ) use ($selectedAreaId) {
                                $subQ->select('id')->from('work_locations')->where('branch_id', $selectedAreaId);
                            });
                        }
                        if ($selectedLocationId) {
                            $valQuery->where('report_submissions.work_location_id', $selectedLocationId);
                        }

                        if ($agg === 'SUM') {
                            $val = (float) $valQuery->sum('report_submission_values.value_number');
                        } elseif ($agg === 'AVG') {
                            $val = round((float) $valQuery->avg('report_submission_values.value_number'), 1);
                        } elseif ($agg === 'MAX') {
                            $val = (float) $valQuery->max('report_submission_values.value_number');
                        } elseif ($agg === 'MIN') {
                            $val = (float) $valQuery->min('report_submission_values.value_number');
                        } else {
                            $val = (int) $valQuery->count();
                        }
                    } else {
                        $values = collect();
                        foreach ($allFilteredSubmissions as $sub) {
                            foreach ($sub->values as $v) {
                                if ($v->field_name === $metric || ($v->formField && $v->formField->field_name === $metric)) {
                                    $num = $v->value_number ?? (is_numeric($v->value_text) ? (float) $v->value_text : null);
                                    if ($num !== null) $values->push($num);
                                }
                            }
                        }
                        if ($agg === 'SUM') {
                            $val = $values->sum();
                        } elseif ($agg === 'AVG') {
                            $val = $values->count() > 0 ? round($values->avg(), 1) : 0;
                        } elseif ($agg === 'MAX') {
                            $val = $values->count() > 0 ? $values->max() : 0;
                        } elseif ($agg === 'MIN') {
                            $val = $values->count() > 0 ? $values->min() : 0;
                        } else {
                            $val = $values->count();
                        }
                    }
                }
                $prefix = $w['prefix'] ?? '';
                $suffix = $w['suffix'] ?? '';
                $widgetResults[$wId] = [
                    'value' => $val,
                    'formatted_value' => $prefix . number_format($val, $val == (int)$val ? 0 : 2, ',', '.') . $suffix,
                ];
            } elseif (in_array($type, ['bar_chart', 'donut_chart', 'pie_chart', 'line_chart', 'breakdown_table'])) {
                $groups = [];

                if ($totalTemplateSubmissions > 1500) {
                    if ($dim === '_submitted_date') {
                        $diffInMonths = $startDate->diffInMonths($endDate);
                        if ($diffInMonths > 1) {
                            $dateQuery = DB::table('report_submissions')
                                ->where('report_template_id', $template->id)
                                ->whereBetween('submitted_at', [$startDate, $endDate])
                                ->selectRaw("TO_CHAR(submitted_at, 'YYYY-MM') as period_key, count(*) as total")
                                ->groupBy('period_key')
                                ->orderBy('period_key')
                                ->pluck('total', 'period_key');
                            
                            $currentPeriod = $startDate->copy()->startOfMonth();
                            while ($currentPeriod->lte($endDate)) {
                                $k = $currentPeriod->format('Y-m');
                                $label = $currentPeriod->translatedFormat('M Y');
                                $groups[$label] = $dateQuery[$k] ?? 0;
                                $currentPeriod->addMonth();
                            }
                        } else {
                            $dateQuery = DB::table('report_submissions')
                                ->where('report_template_id', $template->id)
                                ->whereBetween('submitted_at', [$startDate, $endDate])
                                ->selectRaw("TO_CHAR(submitted_at, 'YYYY-MM-DD') as day_key, count(*) as total")
                                ->groupBy('day_key')
                                ->orderBy('day_key')
                                ->pluck('total', 'day_key');

                            $daysInMonth = $startDate->daysInMonth;
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                $dayObj = Carbon::create($startYear, $startMonth, $d);
                                $k = $dayObj->format('Y-m-d');
                                $dateLabel = $dayObj->translatedFormat('d M');
                                $groups[$dateLabel] = $dateQuery[$k] ?? 0;
                            }
                        }
                    } else {
                        // Group by field values
                        $groupQuery = DB::table('report_submission_values')
                            ->join('report_submissions', 'report_submission_values.report_submission_id', '=', 'report_submissions.id')
                            ->where('report_submissions.report_template_id', $template->id)
                            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
                            ->where('report_submission_values.field_name', $dim)
                            ->selectRaw('report_submission_values.value_text as label, count(*) as total')
                            ->groupBy('label')
                            ->orderByDesc('total')
                            ->limit(10)
                            ->pluck('total', 'label')
                            ->toArray();
                        $groups = $groupQuery;
                    }
                } else {

                if ($dim === '_submitted_date') {
                    // Group by Month or Day depending on range length
                    $diffInMonths = $startDate->diffInMonths($endDate);
                    if ($diffInMonths > 1) {
                        $currentPeriod = $startDate->copy()->startOfMonth();
                        while ($currentPeriod->lte($endDate)) {
                            $label = $currentPeriod->translatedFormat('M Y');
                            $groups[$label] = 0;
                            $currentPeriod->addMonth();
                        }
                        foreach ($allFilteredSubmissions as $sub) {
                            if ($sub->submitted_at) {
                                $label = $sub->submitted_at->translatedFormat('M Y');
                                if (isset($groups[$label])) {
                                    $groups[$label] += 1;
                                }
                            }
                        }
                    } else {
                        $daysInMonth = $startDate->daysInMonth;
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $dateLabel = Carbon::create($startYear, $startMonth, $d)->translatedFormat('d M');
                            $groups[$dateLabel] = 0;
                        }
                        foreach ($allFilteredSubmissions as $sub) {
                            if ($sub->submitted_at) {
                                $dateLabel = $sub->submitted_at->translatedFormat('d M');
                                if (isset($groups[$dateLabel])) {
                                    if ($metric && $metric !== '_count' && $metric !== '_submission') {
                                        $valNum = 0;
                                        foreach ($sub->values as $v) {
                                            if ($v->field_name === $metric || ($v->formField && $v->formField->field_name === $metric)) {
                                                $valNum += $v->value_number ?? (is_numeric($v->value_text) ? (float)$v->value_text : 0);
                                            }
                                        }
                                        $groups[$dateLabel] += $valNum;
                                    } else {
                                        $groups[$dateLabel] += 1;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($dim === '_employee' || $dim === 'employee_id') {
                    foreach ($allFilteredSubmissions as $sub) {
                        $label = ($sub->employee && $sub->employee->name) ? $sub->employee->name : 'Tanpa Nama';
                        if (!isset($groups[$label])) $groups[$label] = 0;
                        if ($agg === 'COUNT' || empty($metric) || $metric === '_count') {
                            $groups[$label] += 1;
                        } else {
                            foreach ($sub->values as $v) {
                                if ($v->field_name === $metric || ($v->formField && $v->formField->field_name === $metric)) {
                                    $groups[$label] += ($v->value_number ?? (is_numeric($v->value_text) ? (float)$v->value_text : 0));
                                }
                            }
                        }
                    }
                } elseif ($dim === '_store' || $dim === 'work_location_id') {
                    foreach ($allFilteredSubmissions as $sub) {
                        $label = ($sub->workLocation && $sub->workLocation->name) ? $sub->workLocation->name : ($sub->store_name ?? 'Toko Lainnya');
                        if (!isset($groups[$label])) $groups[$label] = 0;
                        if ($agg === 'COUNT' || empty($metric) || $metric === '_count') {
                            $groups[$label] += 1;
                        } else {
                            foreach ($sub->values as $v) {
                                if ($v->field_name === $metric || ($v->formField && $v->formField->field_name === $metric)) {
                                    $groups[$label] += ($v->value_number ?? (is_numeric($v->value_text) ? (float)$v->value_text : 0));
                                }
                            }
                        }
                    }
                } elseif ($dim === '_status') {
                    foreach ($allFilteredSubmissions as $sub) {
                        $label = ucfirst($sub->status ?? 'pending');
                        if (!isset($groups[$label])) $groups[$label] = 0;
                        $groups[$label] += 1;
                    }
                } else {
                    foreach ($allFilteredSubmissions as $sub) {
                        $dimVal = null;
                        $measureVal = 1;

                        foreach ($sub->values as $v) {
                            if ($v->field_name === $dim || ($v->formField && $v->formField->field_name === $dim)) {
                                $dimVal = $v->value_text ?? $v->value_number;
                            }
                            if ($metric && ($v->field_name === $metric || ($v->formField && $v->formField->field_name === $metric))) {
                                $measureVal = $v->value_number ?? (is_numeric($v->value_text) ? (float)$v->value_text : 1);
                            }
                        }

                        if (!empty($dimVal)) {
                            $dimStr = (string) $dimVal;
                            if (!isset($groups[$dimStr])) $groups[$dimStr] = 0;
                            if ($agg === 'COUNT') {
                                $groups[$dimStr] += 1;
                            } else {
                                $groups[$dimStr] += $measureVal;
                            }
                        }
                    }
                }
            }

                if ($dim !== '_submitted_date') {
                    arsort($groups);
                    if (count($groups) > 10) {
                        $topGroups = array_slice($groups, 0, 10, true);
                        $otherSum = array_sum(array_slice($groups, 10));
                        if ($otherSum > 0 && in_array($type, ['donut_chart', 'pie_chart'])) {
                            $topGroups['Lainnya'] = $otherSum;
                        }
                        $groups = $topGroups;
                    }
                }

                $categories = array_keys($groups);
                $seriesData = array_values($groups);

                $widgetResults[$wId] = [
                    'categories' => $categories,
                    'series' => $seriesData,
                    'groups' => $groups,
                    'total' => array_sum($seriesData),
                ];
            }
        }

        // Dropdown Data for Region, Area, Store
        $regions = WorkLocation::where(function($q) use ($scopedPrincipalIds) {
            $q->whereIn('principal_id', $scopedPrincipalIds)->orWhereNull('principal_id');
        })->whereNotNull('region')->where('region', '!=', '')->distinct()->orderBy('region')->pluck('region')->toArray();

        if (empty($regions)) {
            $regions = Branch::whereNotNull('region')->where('region', '!=', '')->distinct()->orderBy('region')->pluck('region')->toArray();
        }

        $areaQuery = Branch::query();
        if ($selectedRegion) {
            $areaQuery->where('region', $selectedRegion);
        }
        $areas = $areaQuery->where(function($q) use ($scopedPrincipalIds) {
            $q->whereIn('id', function($sub) use ($scopedPrincipalIds) {
                $sub->select('branch_id')->from('employees')->whereIn('principal_id', $scopedPrincipalIds)->whereNotNull('branch_id');
            })->orWhereIn('id', function($sub) use ($scopedPrincipalIds) {
                $sub->select('branch_id')->from('work_locations')->whereIn('principal_id', $scopedPrincipalIds)->whereNotNull('branch_id');
            });
        })->orderBy('name')->get();

        if ($areas->isEmpty()) {
            $areas = Branch::orderBy('name')->get();
        }

        $storeQuery = WorkLocation::query();
        if ($selectedRegion) {
            $storeQuery->where('region', $selectedRegion);
        }
        if ($selectedAreaId) {
            $storeQuery->where('branch_id', $selectedAreaId);
        }
        $workLocations = $storeQuery->where(function($q) use ($scopedPrincipalIds) {
            $q->whereIn('work_locations.principal_id', $scopedPrincipalIds)->orWhereNull('work_locations.principal_id');
        })->orderBy('name')->get();

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.report_detail', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'template',
            'submissions',
            'totalTemplateSubmissions',
            'uniqueStores',
            'startMonth',
            'startYear',
            'endMonth',
            'endYear',
            'search',
            'selectedRegion',
            'selectedAreaId',
            'selectedLocationId',
            'regions',
            'areas',
            'workLocations',
            'setting',
            'dashboardConfig',
            'widgetResults'
        ));
    }

    /**
     * Save custom dashboard configuration for a report template (Studio Builder)
     */
    public function saveDashboardConfig(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->firstOrFail();

        $config = $request->input('dashboard_config');
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        if (empty($config) || !isset($config['widgets'])) {
            return response()->json(['success' => false, 'message' => 'Format konfigurasi dashboard tidak valid.'], 422);
        }

        $template->dashboard_config = $config;
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Tata letak dashboard laporan berhasil disimpan!',
            'dashboard_config' => $config,
        ]);
    }

    /**
     * Reset dashboard configuration back to default
     */
    public function resetDashboardConfig(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->firstOrFail();

        $template->dashboard_config = null;
        $template->save();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard berhasil dikembalikan ke tampilan standar bawaan!',
        ]);
    }

    /**
     * View detailed individual report submission on Principal Portal
     */
    public function submissionDetail(Request $request, string $code, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->with('fields')
            ->firstOrFail();

        $submission = ReportSubmission::where('id', $id)
            ->where('report_template_id', $template->id)
            ->whereIn('principal_id', $scopedPrincipalIds)
            ->with([
                'employee.branch',
                'employee.position',
                'workLocation',
                'itineraryItem',
                'values.formField',
                'verifier'
            ])
            ->firstOrFail();

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.report_submission_detail', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'template',
            'submission',
            'setting'
        ));
    }

    /**
     * Update Approval / Verification Status for a Report Submission on Principal Portal
     */
    public function updateSubmissionStatus(Request $request, string $code, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->firstOrFail();

        $submission = ReportSubmission::where('id', $id)
            ->where('report_template_id', $template->id)
            ->whereIn('principal_id', $scopedPrincipalIds)
            ->firstOrFail();

        $status = $request->input('status');
        if (!in_array($status, ['approved', 'rejected', 'pending'])) {
            return back()->with('error', 'Status verifikasi tidak valid.');
        }

        $notes = $request->input('verification_notes');

        $submission->update([
            'status' => $status,
            'verified_at' => in_array($status, ['approved', 'rejected']) ? now() : null,
            'verified_by' => Auth::id(),
            'verification_notes' => $notes !== null ? $notes : $submission->verification_notes,
        ]);

        $msg = match ($status) {
            'approved' => 'Laporan berhasil disetujui & diverifikasi (Valid).',
            'rejected' => 'Laporan berhasil ditolak.',
            default => 'Status laporan dikembalikan ke Menunggu Verifikasi.',
        };

        return back()->with('success', $msg);
    }

    /**
     * Export Submissions for a Template to CSV / Excel Download
     */
    public function exportReport(Request $request, string $code)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.code', $code)
            ->with('fields')
            ->firstOrFail();

        $startMonth = (int) ($request->query('start_month') ?? $request->query('month') ?? Carbon::now()->month);
        $startYear  = (int) ($request->query('start_year') ?? $request->query('year') ?? Carbon::now()->year);
        $endMonth   = (int) ($request->query('end_month') ?? $startMonth);
        $endYear    = (int) ($request->query('end_year') ?? $startYear);

        $startDate  = Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
        $endDate    = Carbon::createFromDate($endYear, $endMonth, 1)->endOfMonth();

        $selectedRegion     = $request->query('region');
        $selectedAreaId     = $request->query('area_id') ?? $request->query('branch_id');
        $selectedLocationId = $request->query('location_id') ?? $request->query('store_id');

        $query = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with(['employee.branch', 'workLocation.branch', 'values.formField']);

        if ($selectedRegion) {
            $query->where(function ($q) use ($selectedRegion) {
                $q->whereHas('workLocation', fn($w) => $w->where('region', $selectedRegion))
                  ->orWhereHas('employee.branch', fn($b) => $b->where('region', $selectedRegion));
            });
        }
        if ($selectedAreaId) {
            $query->where(function ($q) use ($selectedAreaId) {
                $q->whereHas('workLocation', fn($w) => $w->where('branch_id', $selectedAreaId))
                  ->orWhereHas('employee', fn($e) => $e->where('branch_id', $selectedAreaId));
            });
        }
        if ($selectedLocationId) {
            $query->where('report_submissions.work_location_id', $selectedLocationId);
        }

        $submissions = $query->latest('report_submissions.submitted_at')->get();

        $filename = "rekap-{$template->code}-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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

    /**
     * Master Data Products / SKU Catalog for Principal
     */
    public function productsList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $category = $request->query('category');
        $brand = $request->query('brand');

        $query = Product::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku_code', 'LIKE', "%{$search}%")
                  ->orWhere('barcode', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($brand) {
            $query->where('brand', $brand);
        }

        $products = $query->orderBy('name')->paginate(20);

        $categories = Product::whereIn('principal_id', $scopedPrincipalIds)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        $brands = Product::whereIn('principal_id', $scopedPrincipalIds)
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->toArray();

        $totalProducts = Product::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->count();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.products', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'products',
            'categories',
            'brands',
            'totalProducts',
            'search',
            'category',
            'brand',
            'setting'
        ));
    }

    /**
     * Store a newly created product
     */
    public function storeProduct(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku_code' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'uom' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:4096',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::updateOrCreate(
            ['sku_code' => trim($validated['sku_code'])],
            [
                'principal_id' => $tenantPrincipal->id,
                'company_id' => $tenantPrincipal->company_id,
                'name' => trim($validated['name']),
                'barcode' => !empty($validated['barcode']) ? trim($validated['barcode']) : null,
                'brand' => !empty($validated['brand']) ? trim($validated['brand']) : null,
                'category' => !empty($validated['category']) ? trim($validated['category']) : null,
                'price' => $validated['price'] ?? 0,
                'min_stock' => $validated['min_stock'] ?? 0,
                'uom' => !empty($validated['uom']) ? trim($validated['uom']) : 'Pcs',
                'description' => $validated['description'] ?? null,
                'image_path' => $imagePath,
                'is_active' => true,
            ]
        );

        return redirect()->route('portal.products')->with('success', 'Produk SKU baru berhasil ditambahkan!');
    }

    /**
     * Update an existing product
     */
    public function updateProduct(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $product = Product::whereIn('principal_id', $scopedPrincipalIds)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku_code' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'uom' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $product->image_path = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => trim($validated['name']),
            'sku_code' => trim($validated['sku_code']),
            'barcode' => !empty($validated['barcode']) ? trim($validated['barcode']) : null,
            'brand' => !empty($validated['brand']) ? trim($validated['brand']) : null,
            'category' => !empty($validated['category']) ? trim($validated['category']) : null,
            'price' => $validated['price'] ?? 0,
            'min_stock' => $validated['min_stock'] ?? 0,
            'uom' => !empty($validated['uom']) ? trim($validated['uom']) : 'Pcs',
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('portal.products')->with('success', 'Data produk berhasil diperbarui!');
    }

    /**
     * Delete a product
     */
    public function destroyProduct(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $product = Product::whereIn('principal_id', $scopedPrincipalIds)->findOrFail($id);
        $product->delete();

        return redirect()->route('portal.products')->with('success', 'Produk berhasil dihapus!');
    }

    /**
     * Download Excel / CSV Import Template
     */
    public function downloadTemplateImport(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_produk_sku.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header format
            fputcsv($handle, ['nama_produk', 'kode_sku', 'barcode', 'brand', 'kategori', 'harga', 'satuan', 'stok_minimal', 'deskripsi']);

            // Sample rows
            fputcsv($handle, ['SoKlin Liquid Antibacterial 720ml', 'WNG-SKL-LIQ-720', '8998866101102', 'SoKlin', 'Care / Detergent', '19500', 'Pouch', '12', 'Deterjen cair konsentrat antibakteri']);
            fputcsv($handle, ['Mie Sedaap Goreng Original 90g', 'WNG-MSD-GRG-90', '8998866200010', 'Mie Sedaap', 'Food & Beverage', '3200', 'Bks', '40', 'Mie instan goreng bawang renyah']);
            fputcsv($handle, ['Nuvo Family Soap 76g', 'LNW-NVO-MRH-76', '8998866600015', 'Nuvo', 'Personal Care', '4500', 'Pcs', '24', 'Sabun mandi antibakteri']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import products from Excel / CSV file
     */
    public function importProducts(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];

        // 1. If XLSX/XLS, try using PhpSpreadsheet
        if (in_array($extension, ['xlsx', 'xls']) && class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();
            if (!empty($data)) {
                $header = array_shift($data);
                foreach ($data as $row) {
                    if (empty(array_filter($row))) continue;
                    $rowAssoc = [];
                    foreach ($header as $i => $colName) {
                        $key = strtolower(trim((string)$colName));
                        $key = str_replace([' ', '-', '/'], '_', $key);
                        $rowAssoc[$key] = $row[$i] ?? null;
                    }
                    $rows[] = $rowAssoc;
                }
            }
        } else {
            // 2. CSV Parser with auto delimiter detection
            $content = file_get_contents($file->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // Strip BOM
            $lines = preg_split('/\r\n|\r|\n/', trim($content));
            
            if (!empty($lines)) {
                $firstLine = $lines[0];
                $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
                $header = str_getcsv(array_shift($lines), $delimiter);
                $normalizedHeader = [];
                foreach ($header as $col) {
                    $k = strtolower(trim($col));
                    $k = str_replace([' ', '-', '/'], '_', $k);
                    $normalizedHeader[] = $k;
                }

                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    $cols = str_getcsv($line, $delimiter);
                    $rowAssoc = [];
                    foreach ($normalizedHeader as $idx => $k) {
                        $rowAssoc[$k] = $cols[$idx] ?? null;
                    }
                    $rows[] = $rowAssoc;
                }
            }
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'File tidak berisi data atau format tidak sesuai.');
        }

        $imported = 0;
        foreach ($rows as $r) {
            $name = $r['nama_produk'] ?? $r['name'] ?? $r['nama'] ?? $r['produk'] ?? null;
            $sku = $r['kode_sku'] ?? $r['sku_code'] ?? $r['sku'] ?? $r['kode'] ?? null;

            if (empty($name) && empty($sku)) {
                continue;
            }

            if (empty($sku)) {
                $sku = 'SKU-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$name), 0, 8)) . '-' . rand(100, 999);
            }
            if (empty($name)) {
                $name = $sku;
            }

            $barcode = $r['barcode'] ?? $r['ean'] ?? $r['kode_barcode'] ?? null;
            $brand = $r['brand'] ?? $r['merek'] ?? $r['merk'] ?? null;
            $category = $r['kategori'] ?? $r['category'] ?? null;
            $priceRaw = $r['harga'] ?? $r['price'] ?? $r['harga_standar'] ?? 0;
            $price = (float) preg_replace('/[^0-9.]/', '', (string)$priceRaw);
            $minStockRaw = $r['stok_minimal'] ?? $r['min_stock'] ?? $r['minimal_stock'] ?? $r['minimum_stock'] ?? $r['stock_minimal'] ?? $r['min_stock_qty'] ?? $r['stok_min'] ?? 0;
            $minStock = (int) preg_replace('/[^0-9]/', '', (string)$minStockRaw);
            $uom = $r['satuan'] ?? $r['uom'] ?? 'Pcs';
            $description = $r['deskripsi'] ?? $r['description'] ?? $r['keterangan'] ?? null;

            Product::updateOrCreate(
                ['sku_code' => trim((string)$sku)],
                [
                    'principal_id' => $tenantPrincipal->id,
                    'company_id' => $tenantPrincipal->company_id,
                    'name' => trim((string)$name),
                    'barcode' => !empty($barcode) ? trim((string)$barcode) : null,
                    'brand' => !empty($brand) ? trim((string)$brand) : null,
                    'category' => !empty($category) ? trim((string)$category) : null,
                    'price' => $price,
                    'min_stock' => $minStock,
                    'uom' => !empty($uom) ? trim((string)$uom) : 'Pcs',
                    'description' => $description,
                    'is_active' => true,
                ]
            );

            $imported++;
        }

        return redirect()->route('portal.products')->with('success', "Berhasil mengimpor {$imported} data produk ke katalog!");
    }

    /**
     * Presensi / Attendance Log
     */
    public function attendances(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->query('q');
        $employeeId = $request->query('employee_id');
        $locationId = $request->query('location_id');
        $status = $request->query('status');

        $query = Attendance::whereHas('employee', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($locationId) {
            $query->whereHas('employeeSchedule', function ($q) use ($locationId) {
                $q->where('work_location_id', $locationId);
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'present' => (clone $query)->whereIn('status', ['present', 'on_time', 'hadir'])->count(),
            'late' => (clone $query)->whereIn('status', ['late', 'terlambat'])->count(),
            'leave' => (clone $query)->whereIn('status', ['leave', 'cuti', 'izin', 'sick', 'sakit'])->count(),
        ];

        $attendances = $query->with(['employee.branch', 'employee.position', 'checkinLog', 'checkoutLog', 'employeeSchedule.workLocation', 'employeeSchedule.shift'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('checkin_at', 'desc')
            ->paginate(20);

        $employees = Employee::whereIn('employees.principal_id', $scopedPrincipalIds)->orderBy('full_name')->get();
        $workLocations = WorkLocation::whereIn('principal_id', $scopedPrincipalIds)->orWhereNull('principal_id')->orderBy('name')->get();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.attendances', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'attendances', 'stats', 'startDate', 'endDate', 'search', 'employeeId',
            'locationId', 'status', 'employees', 'workLocations', 'setting'
        ));
    }

    /**
     * Export Attendance Logs to CSV
     */
    public function exportAttendances(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        $query = Attendance::whereHas('employee', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        $attendances = $query->with(['employee.branch', 'employee.position', 'checkinLog', 'checkoutLog', 'employeeSchedule.workLocation', 'employeeSchedule.shift'])
            ->orderBy('attendance_date', 'desc')->get();

        $filename = 'rekap_presensi_' . \Illuminate\Support\Str::slug($tenantPrincipal->name) . '_' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'NIK', 'Nama Karyawan', 'Jabatan', 'Area / Cabang', 'Toko / Lokasi Kerja', 'Shift', 'Jam Masuk', 'Jam Pulang', 'Status', 'Keterlambatan (Menit)', 'Durasi Kerja (Menit)', 'Alamat / GPS Checkin']);

            foreach ($attendances as $att) {
                fputcsv($file, [
                    $att->attendance_date,
                    $att->employee?->nik ?? '-',
                    $att->employee?->full_name ?? '-',
                    $att->employee?->position?->name ?? '-',
                    $att->employee?->branch?->name ?? '-',
                    $att->employeeSchedule?->workLocation?->name ?? ($att->employee?->workLocation?->name ?? '-'),
                    $att->employeeSchedule?->shift?->name ?? 'Default Shift',
                    $att->checkin_at ? Carbon::parse($att->checkin_at)->format('H:i:s') : '-',
                    $att->checkout_at ? Carbon::parse($att->checkout_at)->format('H:i:s') : '-',
                    strtoupper($att->status ?? 'PRESENT'),
                    $att->late_minutes ?? 0,
                    $att->work_duration_minutes ?? 0,
                    $att->checkinLog?->address_text ?? ($att->checkinLog ? "{$att->checkinLog->latitude}, {$att->checkinLog->longitude}" : '-'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Employees / SPG List
     */
    public function employeesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $branchId = $request->query('branch_id');
        $positionId = $request->query('position_id');
        $status = $request->query('status');

        $query = Employee::whereIn('employees.principal_id', $scopedPrincipalIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($positionId) {
            $query->where('position_id', $positionId);
        }
        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
        ];

        $employees = $query->with(['branch', 'position', 'company', 'workLocation'])
            ->orderBy('is_active', 'desc')
            ->orderBy('full_name')
            ->paginate(20);

        $branches = Branch::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.employees', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'employees', 'stats', 'search', 'branchId', 'positionId', 'status',
            'branches', 'positions', 'setting'
        ));
    }

    /**
     * Work Locations / Toko List
     */
    public function workLocationsList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $branchId = $request->query('branch_id');

        $assignedLocationIds = DB::table('employee_schedules')
            ->join('employees', 'employees.id', '=', 'employee_schedules.employee_id')
            ->whereIn('employees.principal_id', $scopedPrincipalIds)
            ->whereNotNull('employee_schedules.work_location_id')
            ->pluck('employee_schedules.work_location_id')
            ->merge(
                DB::table('itinerary_items')
                    ->join('itineraries', 'itineraries.id', '=', 'itinerary_items.itinerary_id')
                    ->join('employees', 'employees.id', '=', 'itineraries.employee_id')
                    ->whereIn('employees.principal_id', $scopedPrincipalIds)
                    ->whereNotNull('itinerary_items.work_location_id')
                    ->pluck('itinerary_items.work_location_id')
            )->unique()->filter()->values()->toArray();

        $query = WorkLocation::where(function($q) use ($scopedPrincipalIds, $assignedLocationIds) {
            $q->whereIn('work_locations.principal_id', $scopedPrincipalIds);
            if (!empty($assignedLocationIds)) {
                $q->orWhereIn('work_locations.id', $assignedLocationIds);
            }
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $workLocations = $query->with('branch')->orderBy('name')->paginate(20);

        $branches = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')
                ->whereIn('principal_id', $scopedPrincipalIds)
                ->whereNotNull('branch_id');
        })->orderBy('name')->get();

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.work_locations', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'workLocations', 'search', 'branchId', 'branches', 'setting'
        ));
    }

    /**
     * Shifts List (Scoped strictly to active principal's schedules)
     */
    public function shiftsList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $assignedShiftIds = DB::table('employee_schedules')
            ->join('employees', 'employees.id', '=', 'employee_schedules.employee_id')
            ->whereIn('employees.principal_id', $scopedPrincipalIds)
            ->whereNotNull('employee_schedules.shift_id')
            ->pluck('employee_schedules.shift_id')
            ->unique()->filter()->values()->toArray();

        $query = Shift::where(function ($q) use ($scopedPrincipalIds, $assignedShiftIds) {
            $q->whereIn('principal_id', $scopedPrincipalIds);
            if (!empty($assignedShiftIds)) {
                $q->orWhereIn('id', $assignedShiftIds);
            }
        });

        $shifts = $query->orderBy('name')->paginate(20);
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.shifts', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'shifts', 'setting'
        ));
    }

    /**
     * Areas / Cabang List (Scoped strictly to branches having employees of active principal)
     */
    public function areasList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $query = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')
                ->whereIn('principal_id', $scopedPrincipalIds)
                ->whereNotNull('branch_id');
        });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%");
            });
        }

        $branches = $query->withCount(['employees' => function($eq) use ($scopedPrincipalIds) {
            $eq->whereIn('employees.principal_id', $scopedPrincipalIds)->where('employees.is_active', true);
        }])->orderBy('name')->paginate(20);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.areas', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'branches', 'search', 'setting'
        ));
    }

    /**
     * Schedules / Roster Matrix
     */
    /**
     * Schedules / Roster Matrix
     */
    /**
     * Schedules / Roster Matrix
     */
    public function schedulesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $search = $request->query('q');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = EmployeeSchedule::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $schedules = $query->with(['employee.branch', 'shift', 'workLocation'])
            ->orderBy('schedule_date', 'desc')
            ->paginate(25);

        $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->orderBy('full_name')->get();
        $shifts = Shift::where('is_active', 1)->orWhereNull('is_active')->orderBy('name')->get();
        $workLocations = WorkLocation::whereIn('principal_id', $scopedPrincipalIds)->orWhereNull('principal_id')->orderBy('name')->get();
        $branches = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')->whereIn('principal_id', $scopedPrincipalIds)->whereNotNull('branch_id');
        })->orderBy('name')->get();

        $workingGroups = WorkingGroup::where(function($q) use ($scopedPrincipalIds, $tenantPrincipal) {
            $q->whereIn('principal_id', $scopedPrincipalIds)
              ->orWhere('company_id', $tenantPrincipal->company_id)
              ->orWhereNull('principal_id');
        })->with(['rules.shift', 'members.employee'])->get();

        $user = Auth::user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || $user->hasRole('super_admin') || $user->hasRole('Super Admin') || $user->hasRole('admin'));
        $canCreateRoster = $isSuperAdmin || ($user && ($user->can('create_employee_schedules') || $user->can('manage_roster')));
        $canUpdateRoster = $isSuperAdmin || ($user && ($user->can('update_employee_schedules') || $user->can('manage_roster')));
        $canDeleteRoster = $isSuperAdmin || ($user && ($user->can('delete_employee_schedules') || $user->can('manage_roster')));

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.schedules', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'schedules', 'month', 'year', 'search', 'setting',
            'employees', 'shifts', 'workLocations', 'branches', 'workingGroups',
            'canCreateRoster', 'canUpdateRoster', 'canDeleteRoster'
        ));
    }

    /**
     * Store new employee schedule (single date or date range)
     */
    public function storeSchedule(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'schedule_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:schedule_date',
            'shift_id' => 'nullable|exists:shifts,id',
            'work_location_id' => 'nullable|exists:work_locations,id',
            'schedule_type' => 'required|in:workday,dayoff,holiday,remote,field',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::whereIn('principal_id', $scopedPrincipalIds)->findOrFail($request->employee_id);
        $startDate = Carbon::parse($request->schedule_date);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate->copy();
        $shift = $request->shift_id ? Shift::find($request->shift_id) : null;

        $createdCount = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $plannedStart = null;
            $plannedEnd = null;

            if ($shift && $shift->start_time && $shift->end_time && $request->schedule_type !== 'dayoff') {
                $plannedStart = Carbon::parse($current->toDateString() . ' ' . $shift->start_time);
                $plannedEnd = Carbon::parse($current->toDateString() . ' ' . $shift->end_time);

                if ($shift->is_cross_day ?? false) {
                    $plannedEnd->addDay();
                } elseif ($plannedEnd->lt($plannedStart)) {
                    $plannedEnd->addDay();
                }
            }

            EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'schedule_date' => $current->format('Y-m-d'),
                ],
                [
                    'shift_id' => $request->schedule_type === 'dayoff' ? null : ($shift?->id),
                    'work_location_id' => $request->work_location_id ?: $employee->work_location_id,
                    'schedule_type' => $request->schedule_type,
                    'planned_start_at' => $plannedStart,
                    'planned_end_at' => $plannedEnd,
                    'notes' => $request->notes,
                    'company_id' => $employee->company_id ?? $tenantPrincipal->company_id,
                    'created_by' => Auth::id(),
                ]
            );
            $createdCount++;
            $current->addDay();
        }

        return redirect()->route('portal.schedules', [
            'p' => $tenantPrincipal->id,
            'month' => $startDate->month,
            'year' => $startDate->year
        ])->with('success', "Berhasil menambahkan {$createdCount} hari jadwal roster untuk {$employee->full_name}!");
    }

    /**
     * Generate employee schedules from Working Group rules
     */
    public function generateFromWorkingGroup(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $request->validate([
            'working_group_id' => 'required|exists:working_groups,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $workingGroup = WorkingGroup::with(['rules.shift', 'members.employee'])->findOrFail($request->working_group_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $employeeIds = $request->input('employee_ids', []);
        if (empty($employeeIds)) {
            // Use members or all employees of principal
            $employeeIds = $workingGroup->members()->pluck('employee_id')->toArray();
            if (empty($employeeIds)) {
                $employeeIds = Employee::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->pluck('id')->toArray();
            }
        }

        $employees = Employee::whereIn('id', $employeeIds)->where('is_active', true)->get();
        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada karyawan aktif yang dipilih untuk digenerate.');
        }

        $rules = $workingGroup->rules()->with('shift')->get()->keyBy('day_of_week');
        $defaultShift = $workingGroup->default_shift_id ? Shift::find($workingGroup->default_shift_id) : Shift::first();
        $defaultLocationId = $workingGroup->default_work_location_id;

        $totalGenerated = 0;

        foreach ($employees as $emp) {
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dayName = $current->format('l');
                $rule = $rules->get($dayName);

                $isOff = false;
                $shiftId = null;
                $locationId = $emp->work_location_id ?: $defaultLocationId;
                $schedType = 'workday';

                if ($rule) {
                    $isOff = (bool)$rule->is_day_off;
                    $shiftId = $rule->shift_id ?: ($defaultShift?->id);
                    if ($rule->store_assignment_id) {
                        $locationId = $rule->store_assignment_id;
                    }
                } else {
                    $shiftId = $defaultShift?->id;
                }

                if ($isOff) {
                    $schedType = 'dayoff';
                    $shiftId = null;
                }

                $shift = $shiftId ? Shift::find($shiftId) : null;
                $plannedStart = null;
                $plannedEnd = null;

                if ($shift && $shift->start_time && $shift->end_time && !$isOff) {
                    $plannedStart = Carbon::parse($current->toDateString() . ' ' . $shift->start_time);
                    $plannedEnd = Carbon::parse($current->toDateString() . ' ' . $shift->end_time);
                    if ($shift->is_cross_day ?? false) {
                        $plannedEnd->addDay();
                    } elseif ($plannedEnd->lt($plannedStart)) {
                        $plannedEnd->addDay();
                    }
                }

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'schedule_date' => $current->toDateString(),
                    ],
                    [
                        'shift_id' => $shiftId,
                        'work_location_id' => $locationId,
                        'schedule_type' => $schedType,
                        'planned_start_at' => $plannedStart,
                        'planned_end_at' => $plannedEnd,
                        'notes' => 'Generated via Pola Kerja: ' . $workingGroup->name,
                        'company_id' => $emp->company_id ?? $tenantPrincipal->company_id,
                        'created_by' => Auth::id(),
                    ]
                );

                $totalGenerated++;
                $current->addDay();
            }
        }

        return redirect()->route('portal.schedules', [
            'p' => $tenantPrincipal->id,
            'month' => $startDate->month,
            'year' => $startDate->year
        ])->with('success', "Berhasil mengenerate {$totalGenerated} jadwal roster untuk " . $employees->count() . " karyawan via Pola Kerja {$workingGroup->name}!");
    }

    /**
     * Update an employee schedule
     */
    public function updateSchedule(Request $request, $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $schedule = EmployeeSchedule::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->findOrFail($id);

        $request->validate([
            'schedule_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'work_location_id' => 'nullable|exists:work_locations,id',
            'schedule_type' => 'required|in:workday,dayoff,holiday,remote,field',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = $request->shift_id ? Shift::find($request->shift_id) : null;
        $currentDate = Carbon::parse($request->schedule_date);
        $plannedStart = null;
        $plannedEnd = null;

        if ($shift && $shift->start_time && $shift->end_time && $request->schedule_type !== 'dayoff') {
            $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shift->start_time);
            $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shift->end_time);
            if ($shift->is_cross_day ?? false) {
                $plannedEnd->addDay();
            } elseif ($plannedEnd->lt($plannedStart)) {
                $plannedEnd->addDay();
            }
        }

        $schedule->update([
            'schedule_date' => $request->schedule_date,
            'shift_id' => $request->schedule_type === 'dayoff' ? null : ($shift?->id),
            'work_location_id' => $request->work_location_id ?: $schedule->work_location_id,
            'schedule_type' => $request->schedule_type,
            'planned_start_at' => $plannedStart,
            'planned_end_at' => $plannedEnd,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Jadwal roster berhasil diperbarui!');
    }

    /**
     * Delete an employee schedule
     */
    public function destroySchedule(Request $request, $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $schedule = EmployeeSchedule::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->findOrFail($id);

        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal roster berhasil dihapus!');
    }

    /**
     * Download Excel template for Schedule/Roster import (2 Pilihan Format: Matrix vs Range)
     */
    public function downloadScheduleTemplate(Request $request)
    {
        $type = $request->query('type', 'matrix');
        if ($type === 'range') {
            return Excel::download(new \App\Exports\EmployeeScheduleRangeTemplateExport(), 'Template_Import_Jadwal_Rentang_Tanggal.xlsx');
        }
        return Excel::download(new \App\Exports\EmployeeScheduleMatrixTemplateExport(), 'Template_Import_Jadwal_Per_Tanggal_Matrix.xlsx');
    }

    /**
     * Import schedules from Excel / CSV
     */
    public function importSchedules(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $import = new \App\Imports\EmployeeScheduleImport();
            Excel::import($import, $file->getRealPath());

            $msg = "Berhasil mengimpor {$import->importedCount} jadwal karyawan.";
            if ($import->skippedCount > 0) {
                $msg .= " ({$import->skippedCount} baris dilewati / format tidak sesuai).";
            }

            return redirect()->route('portal.schedules', ['p' => $tenantPrincipal->id])
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal import jadwal: ' . $e->getMessage());
        }
    }

    /**
     * Leave Requests (Izin / Cuti)
     */
    public function leavesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $status = $request->query('status');

        $query = LeaveRequest::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        });

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $leaves = $query->with(['employee.branch', 'employee.position', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.leaves', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'leaves', 'search', 'status', 'setting'
        ));
    }

    /**
     * Extra Hours (Lembur)
     */
    public function extraHoursList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $status = $request->query('status');

        $query = ExtraHour::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        });

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        $extraHours = $query->with(['employee.branch', 'employee.position', 'approver'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.extra_hours', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'extraHours', 'search', 'status', 'setting'
        ));
    }

    /**
     * Team Unchecked Monitoring
     */
    public function uncheckedMonitoring(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $today = Carbon::today()->format('Y-m-d');
        $search = $request->query('q');

        $attendedEmployeeIds = Attendance::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->where('attendance_date', $today)->whereNotNull('checkin_at')->pluck('employee_id')->toArray();

        $query = EmployeeSchedule::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds)->where('employees.is_active', true);
        })->where('schedule_date', $today);

        if (!empty($attendedEmployeeIds)) {
            $query->whereNotIn('employee_id', $attendedEmployeeIds);
        }

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $unchecked = $query->with(['employee.branch', 'employee.position', 'workLocation', 'shift'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.unchecked', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'unchecked', 'today', 'search', 'setting'
        ));
    }

    /**
     * Visit Reports
     */
    public function visitReportsList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $query = VisitReport::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($eq) use ($search) {
                    $eq->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $visitReports = $query->with(['employee.branch', 'itineraryItem.workLocation'])
            ->orderBy('visited_at', 'desc')
            ->paginate(20);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.visit_reports', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'visitReports', 'search', 'setting'
        ));
    }

    /**
     * Visit Schedule (Itinerari) - Interactive Calendar View
     */
    public function itinerariesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $branchId = $request->query('branch_id');
        $employeeId = $request->query('employee_id');
        $search = $request->query('q');

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 7-day grid starting on Monday
        $gridStart = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $query = Itinerary::whereHas('employee', function($q) use ($scopedPrincipalIds, $branchId) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
            if ($branchId) {
                $q->where('employees.branch_id', $branchId);
            }
        })->whereBetween('date', [$gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d')]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $allItineraries = $query->with([
            'employee.position',
            'employee.branch',
            'items.workLocation',
            'items.principal'
        ])->get();

        $itinerariesByDate = $allItineraries->groupBy('date');

        // Build Calendar Days
        $calendarDays = [];
        $currDay = $gridStart->copy();
        $todayStr = Carbon::today()->format('Y-m-d');

        $totalSchedulesInMonth = 0;
        $totalStoresInMonth = 0;

        while ($currDay->lte($gridEnd)) {
            $dateStr = $currDay->format('Y-m-d');
            $isCurrentMonth = ($currDay->month == $month);
            $daySchedules = $itinerariesByDate->get($dateStr, collect());

            if ($isCurrentMonth) {
                $totalSchedulesInMonth += $daySchedules->count();
                foreach ($daySchedules as $ds) {
                    $totalStoresInMonth += $ds->items->count();
                }
            }

            $calendarDays[] = [
                'date_string' => $dateStr,
                'day_number' => $currDay->day,
                'is_current_month' => $isCurrentMonth,
                'is_today' => ($dateStr === $todayStr),
                'schedules' => $daySchedules,
            ];

            $currDay->addDay();
        }

        $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->with(['position', 'branch'])->orderBy('full_name')->get();
        $workLocations = WorkLocation::whereIn('principal_id', $scopedPrincipalIds)->orWhereNull('principal_id')->orderBy('name')->get();
        $branches = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')->whereIn('principal_id', $scopedPrincipalIds)->whereNotNull('branch_id');
        })->orderBy('name')->get();

        $user = Auth::user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || $user->hasRole('super_admin') || $user->hasRole('Super Admin') || $user->hasRole('admin'));
        $canCreateItinerary = $isSuperAdmin || ($user && $user->can('create_itineraries'));
        $canUpdateItinerary = $isSuperAdmin || ($user && $user->can('update_itineraries'));
        $canDeleteItinerary = $isSuperAdmin || ($user && $user->can('delete_itineraries'));

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.itineraries', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'calendarDays', 'month', 'year', 'branchId', 'employeeId', 'search', 'setting',
            'totalSchedulesInMonth', 'totalStoresInMonth',
            'employees', 'workLocations', 'branches',
            'canCreateItinerary', 'canUpdateItinerary', 'canDeleteItinerary'
        ));
    }

    /**
     * Store new Itinerary with multiple route store locations (Single day or Month full)
     */
    public function storeItinerary(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'creation_type' => 'required|in:single,month',
            'date' => 'required_if:creation_type,single|nullable|date',
            'month' => 'required_if:creation_type,month|nullable|integer|between:1,12',
            'year' => 'required_if:creation_type,month|nullable|integer',
            'status' => 'required|in:approved,draft,cancelled',
            'is_strict_routing' => 'nullable',
            'notes' => 'nullable|string|max:500',
            'locations' => 'required|array|min:1',
            'locations.*' => 'required|exists:work_locations,id',
            'visit_types' => 'nullable|array',
        ]);

        $employee = Employee::whereIn('principal_id', $scopedPrincipalIds)->findOrFail($request->employee_id);
        $locations = $request->input('locations', []);
        $visitTypes = $request->input('visit_types', []);
        $isStrict = $request->boolean('is_strict_routing');

        $datesToCreate = [];

        if ($request->creation_type === 'month') {
            $m = (int)$request->month;
            $y = (int)$request->year;
            $start = Carbon::createFromDate($y, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $cur = $start->copy();

            while ($cur->lte($end)) {
                // Skip Sundays (0) if desired or create all Mon-Sat
                if ($cur->dayOfWeek !== 0) {
                    $datesToCreate[] = $cur->format('Y-m-d');
                }
                $cur->addDay();
            }
        } else {
            $datesToCreate[] = Carbon::parse($request->date)->format('Y-m-d');
        }

        $createdCount = 0;

        foreach ($datesToCreate as $d) {
            $itinerary = Itinerary::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $d,
                ],
                [
                    'status' => $request->status,
                    'is_strict_routing' => $isStrict,
                    'notes' => $request->notes,
                ]
            );

            $itinerary->items()->delete();

            foreach ($locations as $idx => $locId) {
                ItineraryItem::create([
                    'itinerary_id' => $itinerary->id,
                    'work_location_id' => $locId,
                    'sequence' => $idx + 1,
                    'is_checkin_location' => ($idx === 0),
                    'principal_id' => $employee->principal_id ?? $tenantPrincipal->id,
                    'visit_type' => $visitTypes[$idx] ?? 'Reguler',
                ]);
            }
            $createdCount++;
        }

        $redirectDate = !empty($datesToCreate) ? Carbon::parse($datesToCreate[0]) : Carbon::today();

        return redirect()->route('portal.itineraries', [
            'p' => $tenantPrincipal->id,
            'month' => $redirectDate->month,
            'year' => $redirectDate->year
        ])->with('success', "Berhasil menambahkan {$createdCount} jadwal itinerari untuk {$employee->full_name} dengan " . count($locations) . " toko kunjungan!");
    }

    /**
     * Update an Itinerary and its route store locations
     */
    public function updateItinerary(Request $request, $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $itinerary = Itinerary::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->findOrFail($id);

        $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:approved,draft,cancelled',
            'is_strict_routing' => 'nullable',
            'notes' => 'nullable|string|max:500',
            'locations' => 'required|array|min:1',
            'locations.*' => 'required|exists:work_locations,id',
            'visit_types' => 'nullable|array',
        ]);

        $itinerary->update([
            'date' => $request->date,
            'status' => $request->status,
            'is_strict_routing' => $request->boolean('is_strict_routing'),
            'notes' => $request->notes,
        ]);

        // Recreate items
        $itinerary->items()->delete();

        $locations = $request->input('locations', []);
        $visitTypes = $request->input('visit_types', []);

        foreach ($locations as $idx => $locId) {
            ItineraryItem::create([
                'itinerary_id' => $itinerary->id,
                'work_location_id' => $locId,
                'sequence' => $idx + 1,
                'is_checkin_location' => ($idx === 0),
                'principal_id' => $itinerary->employee?->principal_id ?? $tenantPrincipal->id,
                'visit_type' => $visitTypes[$idx] ?? 'Reguler',
            ]);
        }

        return redirect()->back()->with('success', 'Rute itinerari berhasil diperbarui!');
    }

    /**
     * Delete an Itinerary
     */
    public function destroyItinerary(Request $request, $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $itinerary = Itinerary::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        })->findOrFail($id);

        $itinerary->items()->delete();
        $itinerary->delete();

        return redirect()->back()->with('success', 'Jadwal itinerari berhasil dihapus!');
    }

    /**
     * Download Excel template for Itinerary import
     */
    public function downloadItineraryTemplate(Request $request)
    {
        return Excel::download(new \App\Exports\VisitScheduleTemplateExport(), 'Template_Import_Visit_Schedule.xlsx');
    }

    /**
     * Import itineraries from Excel / CSV
     */
    public function importItineraries(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $import = new \App\Imports\VisitScheduleImport();
            Excel::import($import, $file->getRealPath());

            $msg = "Berhasil mengimpor {$import->importedCount} jadwal visit itinerari.";
            if ($import->skippedCount > 0) {
                $msg .= " ({$import->skippedCount} baris dilewati / format tidak sesuai).";
            }

            return redirect()->route('portal.itineraries', ['p' => $tenantPrincipal->id])
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal import itinerari: ' . $e->getMessage());
        }
    }

    /**
     * Manpower Report
     */
    public function manpowerReport(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $branchId = $request->query('branch_id');

        $branches = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')
                ->whereIn('principal_id', $scopedPrincipalIds)
                ->whereNotNull('branch_id');
        })->orderBy('name')->get();

        // Calculate manpower per branch & per month (Jan-Dec)
        $branchData = [];
        $monthlyTotals = array_fill(1, 12, 0);

        foreach ($branches as $branch) {
            if ($branchId && $branch->id != $branchId) continue;

            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $start = Carbon::create($year, $m, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                $count = Employee::whereIn('principal_id', $scopedPrincipalIds)
                    ->where('branch_id', $branch->id)
                    ->where('join_date', '<=', $end->format('Y-m-d'))
                    ->where(function($q) use ($start) {
                        $q->whereNull('resign_date')
                          ->orWhere('resign_date', '>=', $start->format('Y-m-d'));
                    })->count();

                $months[$m] = $count;
                $monthlyTotals[$m] += $count;
            }

            $branchData[] = [
                'branch' => $branch,
                'months' => $months,
                'average' => round(array_sum($months) / 12, 1),
            ];
        }

        $totalAverage = round(array_sum($monthlyTotals) / 12, 1);
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.manpower_report', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'branchData', 'monthlyTotals', 'totalAverage', 'year', 'branchId', 'branches', 'setting'
        ));
    }

    /**
     * Mandays Report
     */
    public function mandaysReport(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $month = (int) ($request->query('month') ?? Carbon::now()->month);
        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $branchId = $request->query('branch_id');
        $search = $request->query('q');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $branches = Branch::whereIn('id', function($sub) use ($scopedPrincipalIds) {
            $sub->select('branch_id')->from('employees')
                ->whereIn('principal_id', $scopedPrincipalIds)
                ->whereNotNull('branch_id');
        })->orderBy('name')->get();

        $query = Employee::whereIn('principal_id', $scopedPrincipalIds)
            ->where('is_active', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query->with('branch')->orderBy('full_name')->paginate(20);

        // Calculate mandays for current page employees
        $mandaysData = [];
        $employeeIds = $employees->pluck('id')->toArray();

        $schedulesCount = DB::table('employee_schedules')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('employee_id', DB::raw('count(*) as target_days'))
            ->groupBy('employee_id')
            ->pluck('target_days', 'employee_id')->toArray();

        $attendancesCount = DB::table('attendances')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('checkin_at')
            ->select('employee_id', DB::raw('count(*) as present_days'))
            ->groupBy('employee_id')
            ->pluck('present_days', 'employee_id')->toArray();

        $leavesCount = DB::table('attendances')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', ['leave', 'cuti', 'izin', 'sick', 'sakit'])
            ->select('employee_id', DB::raw('count(*) as leave_days'))
            ->groupBy('employee_id')
            ->pluck('leave_days', 'employee_id')->toArray();

        foreach ($employees as $emp) {
            $target = $schedulesCount[$emp->id] ?? 26;
            $present = $attendancesCount[$emp->id] ?? 0;
            $leave = $leavesCount[$emp->id] ?? 0;
            $pct = $target > 0 ? round(($present / $target) * 100, 1) : 0;

            $mandaysData[] = [
                'employee' => $emp,
                'target_days' => $target,
                'present_days' => $present,
                'leave_days' => $leave,
                'percentage' => $pct,
            ];
        }

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.mandays_report', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'employees', 'mandaysData', 'month', 'year', 'branchId', 'branches', 'search', 'setting'
        ));
    }

    /**
     * Turnover Report
     */
    public function turnoverReport(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $year = (int) ($request->query('year') ?? Carbon::now()->year);

        // Calculate Monthly Turnover (Jan - Dec)
        $turnoverRows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            // Total employees active at start of month
            $startCount = Employee::whereIn('principal_id', $scopedPrincipalIds)
                ->where('join_date', '<', $start->format('Y-m-d'))
                ->where(function($q) use ($start) {
                    $q->whereNull('resign_date')
                      ->orWhere('resign_date', '>=', $start->format('Y-m-d'));
                })->count();

            // Joined this month
            $joined = Employee::whereIn('principal_id', $scopedPrincipalIds)
                ->whereBetween('join_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->count();

            // Resigned this month
            $resigned = Employee::whereIn('principal_id', $scopedPrincipalIds)
                ->whereBetween('resign_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->count();

            // End count
            $endCount = $startCount + $joined - $resigned;
            $avg = ($startCount + $endCount) > 0 ? ($startCount + $endCount) / 2 : 1;
            $rate = round(($resigned / $avg) * 100, 2);

            $turnoverRows[] = [
                'month_num' => $m,
                'month_name' => $start->translatedFormat('F'),
                'start_count' => $startCount,
                'joined' => $joined,
                'resigned' => $resigned,
                'end_count' => $endCount,
                'rate' => $rate,
            ];
        }

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.turnover_report', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'turnoverRows', 'year', 'setting'
        ));
    }
}
