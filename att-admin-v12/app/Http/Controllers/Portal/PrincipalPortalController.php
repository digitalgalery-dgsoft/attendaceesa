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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $hasExplicitDate = $request->has('start_month') || $request->has('month');
        $startMonth = (int) ($request->query('start_month') ?? $request->query('month') ?? 0);
        $startYear  = (int) ($request->query('start_year') ?? $request->query('year') ?? 0);

        // Jika tidak ditentukan di request, pilih secara cerdas bulan terakhir yang memiliki data pada template ini
        if (!$hasExplicitDate || $startMonth <= 0 || $startYear <= 0) {
            $latestSubDate = Cache::remember("rep_tpl_latest_date_{$template->id}", 600, function() use ($template) {
                return DB::table('report_submissions')
                    ->where('report_template_id', $template->id)
                    ->max('submitted_at');
            });
            if ($latestSubDate) {
                $c = Carbon::parse($latestSubDate);
                $startMonth = in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 1 : $c->month;
                $startYear  = in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 2026 : $c->year;
                $endMonth   = (int) ($request->query('end_month') ?? (in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 7 : $c->month));
                $endYear    = (int) ($request->query('end_year') ?? (in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 2026 : $c->year));
            } else {
                $startMonth = in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 1 : Carbon::now()->month;
                $startYear  = in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 2026 : Carbon::now()->year;
                $endMonth   = (int) ($request->query('end_month') ?? (in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 7 : Carbon::now()->month));
                $endYear    = (int) ($request->query('end_year') ?? (in_array($template->code, ['RPT-DULUX-CBP-PRICING', 'RPT-DULUX-OFFTAKE-01']) ? 2026 : Carbon::now()->year));
            }
        }

        $endMonth   = (int) ($request->query('end_month') ?? ($endMonth ?: $startMonth));
        $endYear    = (int) ($request->query('end_year') ?? ($endYear ?: $startYear));

        $startDate  = Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
        $endDate    = Carbon::createFromDate($endYear, $endMonth, 1)->endOfMonth();

        $selectedRegion     = $request->query('region');
        $selectedAreaId     = $request->query('area_id') ?? $request->query('branch_id');
        $selectedLocationId = $request->query('location_id') ?? $request->query('store_id');
        $search             = $request->query('q');

        $isCbpReport = ($template->code === 'RPT-DULUX-CBP-PRICING');
        $brandColor  = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting     = Setting::first();

        // --- CBP Custom Handling (Completely bypasses standard report_submissions queries) ---
        if ($isCbpReport) {
            $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
            $regions = Cache::remember('cbp_filter_regions_v8', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT regional FROM cbp_raw WHERE regional IS NOT NULL AND regional != '' ORDER BY regional");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['R1', 'R2', 'R3', 'R4'];
                }
            });

            // Areas directly from cbp_raw with regional info
            $areas = Cache::remember('cbp_filter_areas_v10', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT regional, MIN(area) as area_name FROM cbp_raw WHERE area IS NOT NULL AND area != '' GROUP BY regional, UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $a['regional']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from cbp_raw with regional & area info
            $workLocations = Cache::remember('cbp_filter_stores_v10', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT regional, MIN(area) as area, name_store FROM cbp_raw WHERE name_store IS NOT NULL GROUP BY name_store ORDER BY name_store ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $result[] = [
                            'id' => $s['name_store'],
                            'name' => $s['name_store'],
                            'region' => $s['regional'],
                            'area' => $s['area']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            $rawPage = max(1, (int)$request->query('raw_page', 1));
            $cbpData = $this->calculateCbpDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $search,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = $cbpData['kpis']['total_records'] ?? 0;
            $uniqueStores = $cbpData['kpis']['unique_stores'] ?? 0;
            $submissions = new LengthAwarePaginator([], $totalTemplateSubmissions, 20, 1);
            $dashboardConfig = [];
            $widgetResults = [];
            $isYtdReport = false;
            $ytdData = [];

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
                'widgetResults',
                'isYtdReport',
                'ytdData',
                'isCbpReport',
                'cbpData'
            ));
        }

        $isOfftakeReport = ($template->code === 'RPT-DULUX-OFFTAKE-01');

        // --- Offtake Custom Handling (Sheet 2 Store Volume Pivot & Sheet 1 Raw Data from offtake_2026.sqlite) ---
        if ($isOfftakeReport) {
            $sqlitePath = storage_path('app/dulux_data/offtake_2026.sqlite');
            $gzPath = storage_path('app/dulux_data/offtake_2026.sqlite.gz');

            // Auto-extract if .sqlite does not exist or corrupted (< 1MB) but .sqlite.gz exists
            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000) {
                if (file_exists($gzPath)) {
                    try {
                        $zp = gzopen($gzPath, 'rb');
                        $tmpPath = $sqlitePath . '.tmp.' . uniqid();
                        $fp = fopen($tmpPath, 'wb');
                        if ($zp && $fp) {
                            while (!gzeof($zp)) {
                                fwrite($fp, gzread($zp, 524288));
                            }
                            gzclose($zp);
                            fclose($fp);
                            @rename($tmpPath, $sqlitePath);
                            @chmod($sqlitePath, 0666);
                        }
                    } catch (\Throwable $e) {
                        \Log::error("Auto-extraction of offtake_2026.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            // Regions directly from offtake_raw
            $regions = Cache::remember('offtake_filter_regions_v2', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT region FROM offtake_raw WHERE region IS NOT NULL AND region != '' ORDER BY region");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['R1', 'R2', 'R3', 'R4'];
                }
            });

            // Areas directly from offtake_raw
            $areas = Cache::remember('offtake_filter_areas_v2', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT region, MIN(area) as area_name FROM offtake_raw WHERE area IS NOT NULL AND area != '' GROUP BY region, UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $a['region']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from offtake_raw
            $workLocations = Cache::remember('offtake_filter_stores_v2', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT region, MIN(area) as area, sap, name_store FROM offtake_raw WHERE name_store IS NOT NULL AND name_store != '' GROUP BY name_store ORDER BY name_store ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $result[] = [
                            'id' => $s['name_store'],
                            'name' => $s['name_store'],
                            'region' => $s['region'],
                            'area' => $s['area'],
                            'sap' => $s['sap']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            $offtakePage = max(1, (int)$request->query('page', 1));
            $rawPage = max(1, (int)$request->query('raw_page', 1));
            $activeTab = $request->query('tab', 'sheet2');

            $offtakeData = $this->calculateOfftakeDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $search,
                $offtakePage,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = 0;
            $uniqueStores = 0;
            $submissions = new LengthAwarePaginator([], 0, 20, 1);
            $dashboardConfig = [];
            $widgetResults = [];
            $isYtdReport = false;
            $ytdData = [];

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
                'widgetResults',
                'isYtdReport',
                'ytdData',
                'isCbpReport',
                'isOfftakeReport',
                'offtakeData',
                'activeTab'
            ));
        }

        // Optimasi Query Statistik Ringkasan (Combined Count dalam 1 Query + Cache 5 Menit)
        $statsCacheKey = 'rep_stats_' . md5($template->id . '_' . $startDate->toDateString() . '_' . $endDate->toDateString() . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search);
        
        $stats = Cache::remember($statsCacheKey, 300, function() use ($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search) {
            $q = DB::table('report_submissions')
                ->where('report_submissions.report_template_id', $template->id)
                ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate]);

            if ($selectedRegion) {
                $q->join('work_locations as wl_reg', 'report_submissions.work_location_id', '=', 'wl_reg.id')
                  ->where('wl_reg.region', $selectedRegion);
            }
            if ($selectedAreaId) {
                $q->where('report_submissions.work_location_id', function($subQ) use ($selectedAreaId) {
                    if (is_numeric($selectedAreaId)) {
                        $subQ->select('id')->from('work_locations')->where('branch_id', $selectedAreaId);
                    } else {
                        $subQ->select('work_locations.id')->from('work_locations')
                            ->join('branches', 'work_locations.branch_id', '=', 'branches.id')
                            ->where('branches.name', $selectedAreaId);
                    }
                });
            }
            if ($selectedLocationId) {
                if (is_numeric($selectedLocationId)) {
                    $q->where('report_submissions.work_location_id', $selectedLocationId);
                } else {
                    $q->whereIn('report_submissions.work_location_id', function($subQ) use ($selectedLocationId) {
                        $subQ->select('id')->from('work_locations')->where('name', $selectedLocationId);
                    });
                }
            }
            if ($search) {
                $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $q->where(function ($subQ) use ($search, $likeOp) {
                    $subQ->whereIn('report_submissions.employee_id', function ($eQ) use ($search, $likeOp) {
                        $eQ->select('id')->from('employees')
                           ->where('full_name', $likeOp, "%{$search}%")
                           ->orWhere('employee_no', $likeOp, "%{$search}%");
                    })->orWhereIn('report_submissions.work_location_id', function ($wQ) use ($search, $likeOp) {
                        $wQ->select('id')->from('work_locations')
                           ->where('name', $likeOp, "%{$search}%");
                    });
                });
            }

            $row = $q->selectRaw('
                COUNT(*) as total_count,
                COUNT(DISTINCT report_submissions.work_location_id) as store_count,
                COUNT(DISTINCT report_submissions.employee_id) as emp_count
            ')->first();

            return [
                'total_count' => (int) ($row->total_count ?? 0),
                'store_count' => (int) ($row->store_count ?? 0),
                'emp_count'   => (int) ($row->emp_count ?? 0),
            ];
        });

        $totalTemplateSubmissions = is_array($stats) ? (int) ($stats['total_count'] ?? 0) : (int) ($stats->total_count ?? 0);
        $uniqueStores = is_array($stats) ? (int) ($stats['store_count'] ?? 0) : (int) ($stats->store_count ?? 0);
        $uniqueEmployees = is_array($stats) ? (int) ($stats['emp_count'] ?? 0) : (int) ($stats->emp_count ?? 0);

        // Optimasi Pagination Item: ambil relasi kolom terdefinisi saja & gunakan LengthAwarePaginator agar bebas query count ganda
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        $itemsQuery = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with([
                'employee.branch',
                'workLocation.branch',
                'values.formField'
            ]);

        if ($selectedRegion) {
            $itemsQuery->whereHas('workLocation', fn($w) => $w->where('region', $selectedRegion));
        }
        if ($selectedAreaId) {
            if (is_numeric($selectedAreaId)) {
                $itemsQuery->whereHas('workLocation', fn($w) => $w->where('branch_id', $selectedAreaId));
            } else {
                $itemsQuery->whereHas('workLocation.branch', fn($b) => $b->where('name', $selectedAreaId));
            }
        }
        if ($selectedLocationId) {
            if (is_numeric($selectedLocationId)) {
                $itemsQuery->where('report_submissions.work_location_id', $selectedLocationId);
            } else {
                $itemsQuery->whereHas('workLocation', fn($w) => $w->where('name', $selectedLocationId));
            }
        }
        if ($search) {
            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $itemsQuery->where(function ($q) use ($search, $likeOp) {
                $q->whereHas('employee', function ($sub) use ($search, $likeOp) {
                    $sub->where('employees.full_name', $likeOp, "%{$search}%")
                        ->orWhere('employees.employee_no', $likeOp, "%{$search}%");
                })->orWhereHas('workLocation', function ($sub) use ($search, $likeOp) {
                    $sub->where('work_locations.name', $likeOp, "%{$search}%");
                });
            });
        }

        $items = $itemsQuery->latest('report_submissions.submitted_at')
            ->forPage($page, $perPage)
            ->get();

        $submissions = new LengthAwarePaginator(
            $items,
            $totalTemplateSubmissions,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Dynamic Dashboard Configuration & Widget Calculation Engine (Cached 5 Menit)
        $dashboardConfig = $template->resolved_dashboard_config;
        $widgets = $dashboardConfig['widgets'] ?? [];
        
        $widgetsCacheKey = 'rep_widgets_' . md5($template->id . '_' . $startDate->toDateString() . '_' . $endDate->toDateString() . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search);
        
        $widgetResults = Cache::remember($widgetsCacheKey, 300, function() use ($widgets, $totalTemplateSubmissions, $uniqueStores, $uniqueEmployees, $template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $startYear, $startMonth, $search) {
            $results = [];
            $driver = DB::connection()->getDriverName();

            // Helper function to apply common filters to any report_submissions query
            $applySubFilters = function($query) use ($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $driver) {
                $query->where('report_submissions.report_template_id', $template->id)
                      ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate]);

                if ($selectedRegion) {
                    $query->join('work_locations as wl_reg', 'report_submissions.work_location_id', '=', 'wl_reg.id')
                          ->where('wl_reg.region', $selectedRegion);
                }
                if ($selectedAreaId) {
                    $query->where('report_submissions.work_location_id', function($subQ) use ($selectedAreaId) {
                        if (is_numeric($selectedAreaId)) {
                            $subQ->select('id')->from('work_locations')->where('branch_id', $selectedAreaId);
                        } else {
                            $subQ->select('work_locations.id')->from('work_locations')
                                ->join('branches', 'work_locations.branch_id', '=', 'branches.id')
                                ->where('branches.name', $selectedAreaId);
                        }
                    });
                }
                if ($selectedLocationId) {
                    if (is_numeric($selectedLocationId)) {
                        $query->where('report_submissions.work_location_id', $selectedLocationId);
                    } else {
                        $query->whereIn('report_submissions.work_location_id', function($subQ) use ($selectedLocationId) {
                            $subQ->select('id')->from('work_locations')->where('name', $selectedLocationId);
                        });
                    }
                }
                if ($search) {
                    $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
                    $query->where(function ($subQ) use ($search, $likeOp) {
                        $subQ->whereIn('report_submissions.employee_id', function ($eQ) use ($search, $likeOp) {
                            $eQ->select('id')->from('employees')
                               ->where('full_name', $likeOp, "%{$search}%")
                               ->orWhere('employee_no', $likeOp, "%{$search}%");
                        })->orWhereIn('report_submissions.work_location_id', function ($wQ) use ($search, $likeOp) {
                            $wQ->select('id')->from('work_locations')
                               ->where('name', $likeOp, "%{$search}%");
                        });
                    });
                }
                return $query;
            };

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
                        $valQuery = DB::table('report_submission_values')
                            ->where('report_submission_values.field_name', $metric)
                            ->whereIn('report_submission_values.report_submission_id', function($subQ) use ($applySubFilters) {
                                $applySubFilters($subQ->select('report_submissions.id')->from('report_submissions'));
                            });

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
                    }

                    $prefix = $w['prefix'] ?? '';
                    $suffix = $w['suffix'] ?? '';
                    $results[$wId] = [
                        'value' => $val,
                        'formatted_value' => $prefix . number_format($val, $val == (int)$val ? 0 : 2, ',', '.') . $suffix,
                    ];
                } elseif (in_array($type, ['bar_chart', 'donut_chart', 'pie_chart', 'line_chart', 'breakdown_table'])) {
                    $groups = [];

                    if ($dim === '_submitted_date') {
                        $diffInMonths = $startDate->diffInMonths($endDate);
                        if ($diffInMonths > 1) {
                            $periodExpr = $driver === 'pgsql' ? "TO_CHAR(report_submissions.submitted_at, 'YYYY-MM')" : "DATE_FORMAT(report_submissions.submitted_at, '%Y-%m')";
                            $dateQuery = DB::table('report_submissions');
                            $applySubFilters($dateQuery);
                            $dateResults = $dateQuery->selectRaw("{$periodExpr} as period_key, count(*) as total")
                                ->groupBy('period_key')
                                ->orderBy('period_key')
                                ->pluck('total', 'period_key');
                            
                            $currentPeriod = $startDate->copy()->startOfMonth();
                            while ($currentPeriod->lte($endDate)) {
                                $k = $currentPeriod->format('Y-m');
                                $label = $currentPeriod->translatedFormat('M Y');
                                $groups[$label] = $dateResults[$k] ?? 0;
                                $currentPeriod->addMonth();
                            }
                        } else {
                            $dayExpr = $driver === 'pgsql' ? "TO_CHAR(report_submissions.submitted_at, 'YYYY-MM-DD')" : "DATE_FORMAT(report_submissions.submitted_at, '%Y-%m-%d')";
                            $dateQuery = DB::table('report_submissions');
                            $applySubFilters($dateQuery);
                            $dateResults = $dateQuery->selectRaw("{$dayExpr} as day_key, count(*) as total")
                                ->groupBy('day_key')
                                ->orderBy('day_key')
                                ->pluck('total', 'day_key');

                            $daysInMonth = $startDate->daysInMonth;
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                $dayObj = Carbon::create($startYear, $startMonth, $d);
                                $k = $dayObj->format('Y-m-d');
                                $dateLabel = $dayObj->translatedFormat('d M');
                                $groups[$dateLabel] = $dateResults[$k] ?? 0;
                            }
                        }
                    } else {
                        // Group by field values
                        $groupQuery = DB::table('report_submission_values')
                            ->where('report_submission_values.field_name', $dim)
                            ->whereIn('report_submission_values.report_submission_id', function($subQ) use ($applySubFilters) {
                                $applySubFilters($subQ->select('report_submissions.id')->from('report_submissions'));
                            })
                            ->selectRaw('report_submission_values.value_text as label, count(*) as total')
                            ->groupBy('label')
                            ->orderByDesc('total')
                            ->limit(10)
                            ->pluck('total', 'label')
                            ->toArray();
                        $groups = $groupQuery;
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

                    $results[$wId] = [
                        'categories' => $categories,
                        'series' => $seriesData,
                        'groups' => $groups,
                        'total' => array_sum($seriesData),
                    ];
                }
            }

            return $results;
        });

        // Dropdown Data for Region, Area, and Store (Sourced directly from Actual Report Submissions)
        $subLocData = Cache::remember("rep_filter_locs_v3_{$template->id}", 600, function() use ($template) {
            return DB::table('report_submissions')
                ->where('report_submissions.report_template_id', $template->id)
                ->join('work_locations', 'report_submissions.work_location_id', '=', 'work_locations.id')
                ->leftJoin('branches', 'work_locations.branch_id', '=', 'branches.id')
                ->select(
                    'work_locations.id',
                    'work_locations.name',
                    'work_locations.region',
                    'work_locations.branch_id',
                    'branches.name as branch_name'
                )
                ->distinct()
                ->get();
        });

        if ($subLocData->isNotEmpty()) {
            $regions = $subLocData->pluck('region')->filter()->unique()->sort()->values()->toArray();

            $areas = $subLocData->filter(fn($l) => !empty($l->branch_id) && !empty($l->branch_name))
                ->unique('branch_id')
                ->map(function($l) {
                    return (object)[
                        'id' => $l->branch_id,
                        'name' => $l->branch_name,
                        'region' => $l->region
                    ];
                })->sortBy('name')->values();

            $workLocations = $subLocData->map(function($l) {
                return (object)[
                    'id' => $l->id,
                    'name' => $l->name,
                    'region' => $l->region,
                    'area' => $l->branch_name
                ];
            })->sortBy('name')->values();
        } else {
            $regions = [];
            $areas = collect();
            $workLocations = collect();
        }

        // --- YTD Custom Report Logic for Offtake and Stock End ---
        $isYtdReport = false;
        $ytdData = [];
        
        if (in_array($template->code, ['RPT-DULUX-STOCK-END'])) {
            $isYtdReport = true;
            $metricField = 'total_volume_stok_liter';
            $productField = 'produk_stock_end';
            $brandPrefix = 'Stock';
            
            $cacheKeyYtd = 'ytd_report_v2_' . md5($template->id . '_' . $endYear . '_' . $endMonth . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search);
            
            $ytdData = Cache::remember($cacheKeyYtd, 300, function() use ($template, $metricField, $productField, $brandPrefix, $endYear, $endMonth, $selectedRegion, $selectedAreaId, $selectedLocationId, $search) {
                $driver = DB::connection()->getDriverName();

                // Determine Current Year Range (1 Jan to end of selected month)
                $cyStart = \Carbon\Carbon::create($endYear, 1, 1)->startOfDay();
                $cyEnd = \Carbon\Carbon::create($endYear, $endMonth, 1)->endOfMonth()->endOfDay();
                
                // Determine Previous Year Range (1 Jan to end of selected month previous year)
                $pyStart = \Carbon\Carbon::create($endYear - 1, 1, 1)->startOfDay();
                $pyEnd = \Carbon\Carbon::create($endYear - 1, $endMonth, 1)->endOfMonth()->endOfDay();
                
                $calcYtd = function($start, $end) use ($template, $metricField, $productField, $brandPrefix, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $driver) {
                    $q = DB::table('report_submissions')
                        ->join('report_submission_values as rsv_metric', function($j) use ($metricField) {
                            $j->on('report_submissions.id', '=', 'rsv_metric.report_submission_id')
                              ->where('rsv_metric.field_name', '=', $metricField);
                        })
                        ->leftJoin('report_submission_values as rsv_prod', function($j) use ($productField) {
                            $j->on('report_submissions.id', '=', 'rsv_prod.report_submission_id')
                              ->where('rsv_prod.field_name', '=', $productField);
                        })
                        ->where('report_submissions.report_template_id', $template->id)
                        ->whereBetween('report_submissions.submitted_at', [$start, $end]);

                    if ($selectedRegion) {
                        $q->join('work_locations as wl_reg', 'report_submissions.work_location_id', '=', 'wl_reg.id')
                          ->where('wl_reg.region', $selectedRegion);
                    }
                    if ($selectedAreaId) {
                        $q->whereIn('report_submissions.work_location_id', function($subQ) use ($selectedAreaId) {
                            if (is_numeric($selectedAreaId)) {
                                $subQ->select('id')->from('work_locations')->where('branch_id', $selectedAreaId);
                            } else {
                                $subQ->select('work_locations.id')->from('work_locations')
                                    ->join('branches', 'work_locations.branch_id', '=', 'branches.id')
                                    ->where('branches.name', $selectedAreaId);
                            }
                        });
                    }
                    if ($selectedLocationId) {
                        if (is_numeric($selectedLocationId)) {
                            $q->where('report_submissions.work_location_id', $selectedLocationId);
                        } else {
                            $q->whereIn('report_submissions.work_location_id', function($subQ) use ($selectedLocationId) {
                                $subQ->select('id')->from('work_locations')->where('name', $selectedLocationId);
                            });
                        }
                    }
                    if ($search) {
                        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
                        $q->where(function ($subQ) use ($search, $likeOp) {
                            $subQ->whereIn('report_submissions.employee_id', function ($eQ) use ($search, $likeOp) {
                                $eQ->select('id')->from('employees')
                                   ->where('full_name', $likeOp, "%{$search}%")
                                   ->orWhere('employee_no', $likeOp, "%{$search}%");
                            })->orWhereIn('report_submissions.work_location_id', function ($wQ) use ($search, $likeOp) {
                                $wQ->select('id')->from('work_locations')
                                   ->where('name', $likeOp, "%{$search}%");
                            });
                        });
                    }

                    $caseExpr = "CASE WHEN rsv_prod.value_text ILIKE '%Catylac%' THEN '{$brandPrefix} Catylac' ELSE '{$brandPrefix} Dulux' END";
                    return $q->selectRaw("{$caseExpr} as brand, SUM(COALESCE(rsv_metric.value_number, 0)) as total_vol")
                             ->groupByRaw($caseExpr)
                             ->pluck('total_vol', 'brand')
                             ->toArray();
                };

                $cyData = $calcYtd($cyStart, $cyEnd);
                $pyData = $calcYtd($pyStart, $pyEnd);

                $allBrands = ["{$brandPrefix} Dulux", "{$brandPrefix} Catylac"];
                $results = [];
                $totalCy = 0;
                $totalPy = 0;

                foreach ($allBrands as $brand) {
                    $cyVol = (float) ($cyData[$brand] ?? 0);
                    $pyVol = (float) ($pyData[$brand] ?? 0);
                    $totalCy += $cyVol;
                    $totalPy += $pyVol;

                    $growth = 0;
                    if ($pyVol > 0) {
                        $growth = (($cyVol - $pyVol) / $pyVol) * 100;
                    } elseif ($cyVol > 0) {
                        $growth = 100;
                    }

                    $results[] = [
                        'brand' => $brand,
                        'cy_volume' => $cyVol,
                        'py_volume' => $pyVol,
                        'growth' => $growth
                    ];
                }

                $totalGrowth = 0;
                if ($totalPy > 0) {
                    $totalGrowth = (($totalCy - $totalPy) / $totalPy) * 100;
                } elseif ($totalCy > 0) {
                    $totalGrowth = 100;
                }

                foreach ($results as &$r) {
                    $r['percentage'] = $totalCy > 0 ? ($r['cy_volume'] / $totalCy) * 100 : 0;
                }
                unset($r);

                // --- Store / Toko Level YTD Calculation ---
                $calcStoreYtd = function($start, $end) use ($template, $metricField, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $driver) {
                    $q = DB::table('report_submissions')
                        ->join('report_submission_values as rsv_metric', function($j) use ($metricField) {
                            $j->on('report_submissions.id', '=', 'rsv_metric.report_submission_id')
                              ->where('rsv_metric.field_name', '=', $metricField);
                        })
                        ->leftJoin('work_locations', 'report_submissions.work_location_id', '=', 'work_locations.id')
                        ->where('report_submissions.report_template_id', $template->id)
                        ->whereBetween('report_submissions.submitted_at', [$start, $end]);

                    if ($selectedRegion) {
                        $q->where('work_locations.region', $selectedRegion);
                    }
                    if ($selectedAreaId) {
                        if (is_numeric($selectedAreaId)) {
                            $q->where('work_locations.branch_id', $selectedAreaId);
                        } else {
                            $q->whereIn('work_locations.branch_id', function($bq) use ($selectedAreaId) {
                                $bq->select('id')->from('branches')->where('name', $selectedAreaId);
                            });
                        }
                    }
                    if ($selectedLocationId) {
                        if (is_numeric($selectedLocationId)) {
                            $q->where('report_submissions.work_location_id', $selectedLocationId);
                        } else {
                            $q->where('work_locations.name', $selectedLocationId);
                        }
                    }
                    if ($search) {
                        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
                        $q->where(function ($subQ) use ($search, $likeOp) {
                            $subQ->whereIn('report_submissions.employee_id', function ($eQ) use ($search, $likeOp) {
                                $eQ->select('id')->from('employees')
                                   ->where('full_name', $likeOp, "%{$search}%")
                                   ->orWhere('employee_no', $likeOp, "%{$search}%");
                            })->orWhere('work_locations.name', $likeOp, "%{$search}%");
                        });
                    }

                    return $q->selectRaw("
                            report_submissions.work_location_id,
                            COALESCE(work_locations.name, 'Toko Tanpa Nama') as store_name,
                            COALESCE(work_locations.region, '-') as region,
                            COALESCE(work_locations.area, '-') as area_name,
                            COALESCE(work_locations.channel, 'Retail') as channel,
                            SUM(COALESCE(rsv_metric.value_number, 0)) as total_vol
                        ")
                        ->groupBy('report_submissions.work_location_id', 'work_locations.name', 'work_locations.region', 'work_locations.area', 'work_locations.channel')
                        ->get();
                };

                $cyStores = $calcStoreYtd($cyStart, $cyEnd);
                $pyStores = $calcStoreYtd($pyStart, $pyEnd);

                $storeMap = [];
                foreach ($cyStores as $row) {
                    $key = $row->work_location_id ? 'id_' . $row->work_location_id : 'name_' . mb_strtolower(trim($row->store_name));
                    $storeMap[$key] = [
                        'work_location_id' => $row->work_location_id,
                        'store_name' => $row->store_name,
                        'region' => $row->region !== '-' ? $row->region : ($row->area_name !== '-' ? $row->area_name : '-'),
                        'area' => $row->area_name,
                        'channel' => $row->channel,
                        'cy_volume' => (float) $row->total_vol,
                        'py_volume' => 0.0,
                    ];
                }

                foreach ($pyStores as $row) {
                    $key = $row->work_location_id ? 'id_' . $row->work_location_id : 'name_' . mb_strtolower(trim($row->store_name));
                    if (isset($storeMap[$key])) {
                        $storeMap[$key]['py_volume'] = (float) $row->total_vol;
                        if ($storeMap[$key]['region'] === '-' && ($row->region !== '-' || $row->area_name !== '-')) {
                            $storeMap[$key]['region'] = $row->region !== '-' ? $row->region : $row->area_name;
                        }
                    } else {
                        $storeMap[$key] = [
                            'work_location_id' => $row->work_location_id,
                            'store_name' => $row->store_name,
                            'region' => $row->region !== '-' ? $row->region : ($row->area_name !== '-' ? $row->area_name : '-'),
                            'area' => $row->area_name,
                            'channel' => $row->channel,
                            'cy_volume' => 0.0,
                            'py_volume' => (float) $row->total_vol,
                        ];
                    }
                }

                $storeDetails = [];
                $totalStoreCy = 0;
                $totalStorePy = 0;

                foreach ($storeMap as $s) {
                    $cy = $s['cy_volume'];
                    $py = $s['py_volume'];
                    $totalStoreCy += $cy;
                    $totalStorePy += $py;

                    $growth = 0;
                    if ($py > 0) {
                        $growth = (($cy - $py) / $py) * 100;
                    } elseif ($cy > 0) {
                        $growth = 100;
                    }

                    $storeDetails[] = [
                        'work_location_id' => $s['work_location_id'],
                        'store_name' => $s['store_name'],
                        'region' => $s['region'],
                        'area' => $s['area'],
                        'channel' => $s['channel'],
                        'cy_volume' => $cy,
                        'py_volume' => $py,
                        'growth' => $growth,
                        'percentage' => 0,
                    ];
                }

                foreach ($storeDetails as &$sd) {
                    $sd['percentage'] = $totalStoreCy > 0 ? ($sd['cy_volume'] / $totalStoreCy) * 100 : 0;
                }
                unset($sd);

                usort($storeDetails, function($a, $b) {
                    return $b['cy_volume'] <=> $a['cy_volume'];
                });

                $totalStoreGrowth = 0;
                if ($totalStorePy > 0) {
                    $totalStoreGrowth = (($totalStoreCy - $totalStorePy) / $totalStorePy) * 100;
                } elseif ($totalStoreCy > 0) {
                    $totalStoreGrowth = 100;
                }

                $top10Stores = array_slice($storeDetails, 0, 10);

                return [
                    'details' => $results,
                    'total' => [
                        'brand' => 'Total DC',
                        'cy_volume' => $totalCy,
                        'py_volume' => $totalPy,
                        'growth' => $totalGrowth,
                        'percentage' => 100
                    ],
                    'stores' => [
                        'details' => $storeDetails,
                        'total' => [
                            'count' => count($storeDetails),
                            'cy_volume' => $totalStoreCy,
                            'py_volume' => $totalStorePy,
                            'growth' => $totalStoreGrowth,
                            'percentage' => 100
                        ],
                        'top10' => $top10Stores
                    ]
                ];
            });
        }
        // --------------------------------------------------------

        $isCbpReport = false;
        $cbpData = [];

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
            'widgetResults',
            'isYtdReport',
            'ytdData',
            'isCbpReport',
            'cbpData'
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

        // Custom Streamed CSV Export for CBP Report (Matching Excel Raw Data format)
        if ($template->code === 'RPT-DULUX-CBP-PRICING') {
            $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
            if (file_exists($sqlitePath)) {
                $filename = "raw-data-cbp-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv";
                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0',
                ];

                $callback = function () use ($sqlitePath, $startMonth, $endMonth, $startYear, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $request) {
                    $handle = fopen('php://output', 'w');
                    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                    $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;
                    $search = $request->query('q');

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    if ($sMonth > $eMonth) {
                        $tmp = $sMonth;
                        $sMonth = $eMonth;
                        $eMonth = $tmp;
                    }
                    $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                    $exportMonths = [];
                    for ($m = $sMonth; $m <= $eMonth; $m++) {
                        if ($m >= 1 && $m <= 7) {
                            $exportMonths[$m] = ($monthNames[$m] ?? "Bln $m") . ' ' . $endYear;
                        }
                    }
                    if (empty($exportMonths)) {
                        for ($m = 1; $m <= 7; $m++) {
                            $exportMonths[$m] = $monthNames[$m] . ' ' . $endYear;
                        }
                    }

                    $headerRow = [
                        'Regional', 'SAP Member', 'SAP Gab', 'Nama Toko', 'Nama TL',
                        'Area Sales', 'RSM Area', 'Class', 'Type', 'Product', 'Category', 'Product Group'
                    ];
                    foreach ($exportMonths as $mKey => $mLabel) {
                        $headerRow[] = "Tin ($mLabel)";
                        $headerRow[] = "Harga Terendah Tin ($mLabel)";
                        $headerRow[] = "REASON Tin ($mLabel)";
                        $headerRow[] = "Galon ($mLabel)";
                        $headerRow[] = "Harga Terendah Galon ($mLabel)";
                        $headerRow[] = "REASON Galon ($mLabel)";
                        $headerRow[] = "Pail ($mLabel)";
                        $headerRow[] = "Harga Terendah Pail ($mLabel)";
                        $headerRow[] = "REASON Pail ($mLabel)";
                    }
                    fputcsv($handle, $headerRow);

                    $activeMonthKeys = array_keys($exportMonths);
                    $whereClauses = ["month IN (" . implode(',', $activeMonthKeys) . ")"];
                    $params = [];

                    if ($selectedRegion) {
                        $whereClauses[] = "regional = ?";
                        $params[] = $selectedRegion;
                    }
                    if ($selectedAreaName) {
                        $whereClauses[] = "(UPPER(area) LIKE ? OR UPPER(rsm_area) LIKE ?)";
                        $params[] = "%" . strtoupper($selectedAreaName) . "%";
                        $params[] = "%" . strtoupper($selectedAreaName) . "%";
                    }
                    if ($selectedStoreName) {
                        $whereClauses[] = "name_store LIKE ?";
                        $params[] = "%$selectedStoreName%";
                    }
                    if ($search) {
                        $whereClauses[] = "(product LIKE ? OR brand LIKE ? OR name_store LIKE ? OR sap_member LIKE ? OR sap_gab LIKE ? OR tl_name LIKE ?)";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                    }

                    $whereSql = implode(" AND ", $whereClauses);

                    $itemStmt = $pdo->prepare("
                        SELECT code, regional, sap_member, sap_gab, name_store, tl_name, area, rsm_area, class, store_type, product, category, product_group
                        FROM cbp_raw
                        WHERE $whereSql
                        GROUP BY code
                        ORDER BY regional, area, name_store, product
                    ");
                    $itemStmt->execute($params);

                    $priceStmt = $pdo->prepare("
                        SELECT code, month,
                               price_tin, lowest_tin, reason_tin,
                               price_galon, lowest_galon, reason_galon,
                               price_pail, lowest_pail, reason_pail
                        FROM cbp_raw
                        WHERE $whereSql
                    ");
                    $priceStmt->execute($params);
                    $pivoted = [];
                    while ($pr = $priceStmt->fetch(\PDO::FETCH_ASSOC)) {
                        $pivoted[$pr['code']][$pr['month']] = $pr;
                    }

                    while ($it = $itemStmt->fetch(\PDO::FETCH_ASSOC)) {
                        $row = [
                            $it['regional'] ?? '',
                            $it['sap_member'] ?? '',
                            $it['sap_gab'] ?? '',
                            $it['name_store'] ?? '',
                            $it['tl_name'] ?? '',
                            $it['area'] ?? '',
                            $it['rsm_area'] ?? '',
                            $it['class'] ?? '',
                            $it['store_type'] ?? '',
                            $it['product'] ?? '',
                            $it['category'] ?? '',
                            $it['product_group'] ?? '',
                        ];

                        $codePrices = $pivoted[$it['code']] ?? [];
                        foreach ($activeMonthKeys as $mKey) {
                            $mp = $codePrices[$mKey] ?? null;
                            $row[] = (!empty($mp['price_tin']) && $mp['price_tin'] > 0) ? $mp['price_tin'] : '';
                            $row[] = (!empty($mp['lowest_tin']) && $mp['lowest_tin'] > 0) ? $mp['lowest_tin'] : ((!empty($mp['price_tin']) && $mp['price_tin'] > 0) ? $mp['price_tin'] : '');
                            $row[] = $mp['reason_tin'] ?? '';
                            $row[] = (!empty($mp['price_galon']) && $mp['price_galon'] > 0) ? $mp['price_galon'] : '';
                            $row[] = (!empty($mp['lowest_galon']) && $mp['lowest_galon'] > 0) ? $mp['lowest_galon'] : ((!empty($mp['price_galon']) && $mp['price_galon'] > 0) ? $mp['price_galon'] : '');
                            $row[] = $mp['reason_galon'] ?? '';
                            $row[] = (!empty($mp['price_pail']) && $mp['price_pail'] > 0) ? $mp['price_pail'] : '';
                            $row[] = (!empty($mp['lowest_pail']) && $mp['lowest_pail'] > 0) ? $mp['lowest_pail'] : ((!empty($mp['price_pail']) && $mp['price_pail'] > 0) ? $mp['price_pail'] : '');
                            $row[] = $mp['reason_pail'] ?? '';
                        }

                        fputcsv($handle, $row);
                    }

                    fclose($handle);
                };

                return response()->stream($callback, 200, $headers);
            }
        }

        // Custom Streamed CSV Export for Dulux Offtake Report (Sheet 2 Store Pivot & Sheet 1 Raw Data)
        if ($template->code === 'RPT-DULUX-OFFTAKE-01') {
            $sqlitePath = storage_path('app/dulux_data/offtake_2026.sqlite');
            $gzPath = storage_path('app/dulux_data/offtake_2026.sqlite.gz');
            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000) {
                if (file_exists($gzPath)) {
                    try {
                        $zp = gzopen($gzPath, 'rb');
                        $tmpPath = $sqlitePath . '.tmp.' . uniqid();
                        $fp = fopen($tmpPath, 'wb');
                        if ($zp && $fp) {
                            while (!gzeof($zp)) {
                                fwrite($fp, gzread($zp, 524288));
                            }
                            gzclose($zp);
                            fclose($fp);
                            @rename($tmpPath, $sqlitePath);
                            @chmod($sqlitePath, 0666);
                        }
                    } catch (\Throwable $e) {
                        \Log::error("Auto-extraction of offtake_2026.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            if (file_exists($sqlitePath)) {
                $exportType = $request->query('export_type', 'sheet2');
                $filename = ($exportType === 'raw' ? 'raw-data-offtake' : 'rekap-toko-offtake') . "-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv";
                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0',
                ];

                $callback = function () use ($sqlitePath, $startMonth, $endMonth, $startYear, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $request, $exportType) {
                    $handle = fopen('php://output', 'w');
                    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                    $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;
                    $search = $request->query('q');

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    if ($sMonth > $eMonth) {
                        $tmp = $sMonth;
                        $sMonth = $eMonth;
                        $eMonth = $tmp;
                    }

                    $monthNames = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli',
                        8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    $exportMonths = [];
                    for ($m = $sMonth; $m <= $eMonth; $m++) {
                        if ($m >= 1 && $m <= 7) {
                            $exportMonths[$m] = $monthNames[$m] . ' ' . $endYear;
                        }
                    }
                    if (empty($exportMonths)) {
                        for ($m = 1; $m <= 7; $m++) {
                            $exportMonths[$m] = $monthNames[$m] . ' ' . $endYear;
                        }
                        $sMonth = 1;
                        $eMonth = 7;
                    }

                    $where = ["month BETWEEN ? AND ?"];
                    $params = [$sMonth, $eMonth];

                    if ($selectedRegion) {
                        $where[] = "region = ?";
                        $params[] = $selectedRegion;
                    }
                    if ($selectedAreaName) {
                        $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                        $params[] = $selectedAreaName;
                    }
                    if ($selectedStoreName) {
                        $where[] = "name_store = ?";
                        $params[] = $selectedStoreName;
                    }
                    if ($search) {
                        $where[] = "(name_store LIKE ? OR sap LIKE ?)";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                    }
                    $whereSql = implode(' AND ', $where);

                    if ($exportType === 'raw') {
                        // Sheet 1 format
                        $headerRow = [
                            'Tanggal Transaksi', 'Year', 'Month', 'Week', 'Region', 'Area',
                            'Name Store', 'SAP', 'Sub Brand', 'Brand',
                            'Kemasan Galon', 'Qty Galon', 'Kemasan Pail', 'Qty Pail', 'Volume (L)'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT trans_date, year, month, week, region, area,
                                   name_store, sap, sub_brand, brand,
                                   kemasan_galon, qty_galon, kemasan_pail, qty_pail, volume_liter
                            FROM offtake_raw
                            WHERE $whereSql
                            ORDER BY trans_date ASC, id ASC
                        ");
                        $stmt->execute($params);
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $row['trans_date'] ?? '',
                                $row['year'] ?? '',
                                $row['month'] ?? '',
                                $row['week'] ?? '',
                                $row['region'] ?? '',
                                $row['area'] ?? '',
                                $row['name_store'] ?? '',
                                $row['sap'] ?? '',
                                $row['sub_brand'] ?? '',
                                $row['brand'] ?? '',
                                $row['kemasan_galon'] ?? '',
                                $row['qty_galon'] ?? '',
                                $row['kemasan_pail'] ?? '',
                                $row['qty_pail'] ?? '',
                                $row['volume_liter'] ?? ''
                            ]);
                        }
                    } else {
                        // Sheet 2 format (Store Volume Pivot)
                        $headerRow = ['No', 'SAP', 'Nama Toko', 'Region', 'Area'];
                        foreach ($exportMonths as $mLabel) {
                            $headerRow[] = $mLabel . ' (L)';
                        }
                        $headerRow[] = 'Grand Total (L)';
                        fputcsv($handle, $headerRow);

                        $sumCases = [];
                        foreach (array_keys($exportMonths) as $m) {
                            $sumCases[] = "SUM(CASE WHEN month = $m THEN volume_liter ELSE 0 END) as m_{$m}";
                        }
                        $sumCasesSql = implode(', ', $sumCases);

                        // Stores query
                        $storeSql = "
                            SELECT sap, name_store, MIN(region) as region, MIN(area) as area,
                                   $sumCasesSql,
                                   SUM(volume_liter) as total_vol
                            FROM offtake_raw
                            WHERE $whereSql
                            GROUP BY sap, name_store
                            ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                        ";
                        $stmt = $pdo->prepare($storeSql);
                        $stmt->execute($params);

                        $no = 1;
                        while ($s = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $csvRow = [
                                $no++,
                                $s['sap'],
                                $s['name_store'],
                                $s['region'],
                                $s['area'],
                            ];
                            foreach (array_keys($exportMonths) as $m) {
                                $csvRow[] = round((float)($s["m_{$m}"] ?? 0), 2);
                            }
                            $csvRow[] = round((float)($s['total_vol'] ?? 0), 2);
                            fputcsv($handle, $csvRow);
                        }

                        // Grand total footer row
                        $grandSql = "SELECT $sumCasesSql, SUM(volume_liter) as total_vol FROM offtake_raw WHERE $whereSql";
                        $grandStmt = $pdo->prepare($grandSql);
                        $grandStmt->execute($params);
                        $grand = $grandStmt->fetch(\PDO::FETCH_ASSOC);

                        $footerRow = ['', 'Grand Total', 'Seluruh Toko Terfilter', '', ''];
                        foreach (array_keys($exportMonths) as $m) {
                            $footerRow[] = round((float)($grand["m_{$m}"] ?? 0), 2);
                        }
                        $footerRow[] = round((float)($grand['total_vol'] ?? 0), 2);
                        fputcsv($handle, $footerRow);
                    }

                    fclose($handle);
                };

                return response()->stream($callback, 200, $headers);
            }
        }

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

    /**
     * Calculate Dulux Offtake Data (Sheet 2 Store Volume Pivot & Sheet 1 Raw Data)
     */
    protected function calculateOfftakeDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $offtakePage = 1, $rawPage = 1, $perPage = 50)
    {
        $sqlitePath = storage_path('app/dulux_data/offtake_2026.sqlite');
        $gzPath = storage_path('app/dulux_data/offtake_2026.sqlite.gz');

        // Auto-extract if .sqlite does not exist or corrupted (< 1MB) but .sqlite.gz exists
        if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000) {
            if (file_exists($gzPath)) {
                try {
                    $zp = gzopen($gzPath, 'rb');
                    $tmpPath = $sqlitePath . '.tmp.' . uniqid();
                    $fp = fopen($tmpPath, 'wb');
                    if ($zp && $fp) {
                        while (!gzeof($zp)) {
                            fwrite($fp, gzread($zp, 524288));
                        }
                        gzclose($zp);
                        fclose($fp);
                        @rename($tmpPath, $sqlitePath);
                        @chmod($sqlitePath, 0666);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Auto-extraction of offtake_2026.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($sqlitePath)) {
            return [
                'months' => [],
                'sheet2' => ['stores' => [], 'grand_total' => [], 'total_stores' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                'sheet1' => ['rows' => [], 'total_records' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
            ];
        }

        $sMonth = max(1, min(12, (int)$startMonth));
        $eMonth = max(1, min(12, (int)$endMonth));
        if ($sMonth > $eMonth) {
            $tmp = $sMonth;
            $sMonth = $eMonth;
            $eMonth = $tmp;
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli',
            8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $activeMonths = [];
        for ($m = $sMonth; $m <= $eMonth; $m++) {
            if ($m >= 1 && $m <= 7) {
                $activeMonths[$m] = $monthNames[$m] . ' ' . $endYear;
            }
        }
        if (empty($activeMonths)) {
            for ($m = 1; $m <= 7; $m++) {
                $activeMonths[$m] = $monthNames[$m] . ' ' . $endYear;
            }
            $sMonth = 1;
            $eMonth = 7;
        }

        $cacheKey = 'offtake_dash_v2_' . md5($template->id . '_' . $sMonth . '_' . $eMonth . '_' . $endYear . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search . '_' . $offtakePage . '_' . $rawPage);

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sMonth, $eMonth, $activeMonths, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $offtakePage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = ["month BETWEEN ? AND ?"];
                $params = [$sMonth, $eMonth];

                if ($selectedRegion) {
                    $where[] = "region = ?";
                    $params[] = $selectedRegion;
                }
                if ($selectedAreaId) {
                    $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $params[] = $selectedAreaId;
                }
                if ($selectedLocationId) {
                    $where[] = "name_store = ?";
                    $params[] = $selectedLocationId;
                }
                if ($search) {
                    $where[] = "(name_store LIKE ? OR sap LIKE ?)";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                }
                $whereSql = implode(' AND ', $where);

                // Dynamic sum cases for active months
                $sumCases = [];
                foreach (array_keys($activeMonths) as $m) {
                    $sumCases[] = "SUM(CASE WHEN month = $m THEN volume_liter ELSE 0 END) as m_{$m}";
                }
                $sumCasesSql = implode(', ', $sumCases);

                // 1. Grand Total row across all filtered stores
                $grandSql = "SELECT $sumCasesSql, SUM(volume_liter) as total_vol FROM offtake_raw WHERE $whereSql";
                $grandStmt = $pdo->prepare($grandSql);
                $grandStmt->execute($params);
                $grandRow = $grandStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

                // 2. Count distinct stores
                $countSql = "SELECT COUNT(DISTINCT sap || '---' || name_store) FROM offtake_raw WHERE $whereSql";
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $totalStores = (int)$countStmt->fetchColumn();

                // 3. Paginated Sheet 2 Stores
                $offset = ($offtakePage - 1) * $perPage;
                $storeSql = "
                    SELECT sap, name_store, MIN(region) as region, MIN(area) as area,
                           $sumCasesSql,
                           SUM(volume_liter) as total_vol
                    FROM offtake_raw
                    WHERE $whereSql
                    GROUP BY sap, name_store
                    ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                    LIMIT $perPage OFFSET $offset
                ";
                $storeStmt = $pdo->prepare($storeSql);
                $storeStmt->execute($params);
                $stores = $storeStmt->fetchAll(\PDO::FETCH_ASSOC);

                // 4. Sheet 1 Raw data
                $rawOffset = ($rawPage - 1) * $perPage;
                $rawCountSql = "SELECT COUNT(*) FROM offtake_raw WHERE $whereSql";
                $rawCountStmt = $pdo->prepare($rawCountSql);
                $rawCountStmt->execute($params);
                $totalRaw = (int)$rawCountStmt->fetchColumn();

                $rawSql = "
                    SELECT trans_date, year, month, week, region, area, name_store, sap,
                           sub_brand, brand, kemasan_galon, qty_galon, kemasan_pail, qty_pail, volume_liter
                    FROM offtake_raw
                    WHERE $whereSql
                    ORDER BY trans_date DESC, id DESC
                    LIMIT $perPage OFFSET $rawOffset
                ";
                $rawStmt = $pdo->prepare($rawSql);
                $rawStmt->execute($params);
                $rawRows = $rawStmt->fetchAll(\PDO::FETCH_ASSOC);

                return [
                    'months' => $activeMonths,
                    'sheet2' => [
                        'stores' => $stores,
                        'grand_total' => $grandRow,
                        'total_stores' => $totalStores,
                        'page' => $offtakePage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalStores / $perPage),
                        'from' => $totalStores > 0 ? ($offset + 1) : 0,
                        'to' => min($offset + $perPage, $totalStores),
                    ],
                    'sheet1' => [
                        'rows' => $rawRows,
                        'total_records' => $totalRaw,
                        'page' => $rawPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalRaw / $perPage),
                        'from' => $totalRaw > 0 ? ($rawOffset + 1) : 0,
                        'to' => min($rawOffset + $perPage, $totalRaw),
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Offtake Dashboard: " . $e->getMessage());
                return [
                    'months' => $activeMonths,
                    'sheet2' => ['stores' => [], 'grand_total' => [], 'total_stores' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'sheet1' => ['rows' => [], 'total_records' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
                ];
            }
        });
    }

    /**
     * Calculate CBP Analytics for Dashboard (1) & Dashboard (2) and Raw Data
     */
    protected function calculateCbpDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $rawPage = 1, $rawPerPage = 50)
    {
        $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
        $gzPath = storage_path('app/dulux_data/cbp_2026.sqlite.gz');

        // Auto-extract if .sqlite does not exist or corrupted (< 1MB) but .sqlite.gz exists
        if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000) {
            if (file_exists($gzPath)) {
                try {
                    $zp = gzopen($gzPath, 'rb');
                    $tmpPath = $sqlitePath . '.tmp.' . uniqid();
                    $fp = fopen($tmpPath, 'wb');
                    if ($zp && $fp) {
                        while (!gzeof($zp)) {
                            fwrite($fp, gzread($zp, 524288));
                        }
                        gzclose($zp);
                        fclose($fp);
                        @rename($tmpPath, $sqlitePath);
                        @chmod($sqlitePath, 0666);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Auto-extraction of cbp_2026.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($sqlitePath)) {
            return null;
        }

        $cacheKey = 'cbp_dash_v5_' . md5($template->id . '_' . $startYear . '_' . $startMonth . '_' . $endYear . '_' . $endMonth . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search);

        $aggData = Cache::remember($cacheKey, 300, function() use ($sqlitePath, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $search) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Resolve Area Name & Store Name if filtered
                $selectedAreaName = null;
                if ($selectedAreaId) {
                    $selectedAreaName = is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId;
                }
                $selectedStoreName = null;
                if ($selectedLocationId) {
                    $selectedStoreName = is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId;
                }

                // Prepare Months
                $sMonth = max(1, min(12, (int)$startMonth));
                $eMonth = max(1, min(12, (int)$endMonth));
                if ($sMonth > $eMonth) {
                    $tmp = $sMonth;
                    $sMonth = $eMonth;
                    $eMonth = $tmp;
                }

                $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                $months = [];
                for ($m = $sMonth; $m <= $eMonth; $m++) {
                    if ($m >= 1 && $m <= 7) {
                        $dateObj = Carbon::create($endYear, $m, 1);
                        $months[$m] = [
                            'm' => $m,
                            'short' => $monthNames[$m] ?? "Bln $m",
                            'label' => ($monthNames[$m] ?? "Bln $m") . ' ' . $endYear,
                            'date_header' => strtoupper($dateObj->translatedFormat('F Y'))
                        ];
                    }
                }
                if (empty($months)) {
                    for ($m = 1; $m <= 7; $m++) {
                        $dateObj = Carbon::create($endYear, $m, 1);
                        $months[$m] = [
                            'm' => $m,
                            'short' => $monthNames[$m],
                            'label' => $monthNames[$m] . ' ' . $endYear,
                            'date_header' => strtoupper($dateObj->translatedFormat('F Y'))
                        ];
                    }
                }

                $whereClauses = ["month BETWEEN ? AND ?"];
                $params = [min(array_keys($months)), max(array_keys($months))];

                if ($selectedRegion) {
                    $whereClauses[] = "regional = ?";
                    $params[] = $selectedRegion;
                }
                if ($selectedAreaName) {
                    $whereClauses[] = "(UPPER(area) LIKE ? OR UPPER(rsm_area) LIKE ?)";
                    $params[] = "%" . strtoupper($selectedAreaName) . "%";
                    $params[] = "%" . strtoupper($selectedAreaName) . "%";
                }
                if ($selectedStoreName) {
                    $whereClauses[] = "name_store LIKE ?";
                    $params[] = "%$selectedStoreName%";
                }
                if ($search) {
                    $whereClauses[] = "(product LIKE ? OR brand LIKE ? OR name_store LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                $whereSql = implode(" AND ", $whereClauses);

                // 1. Overall KPIs
                $kpiSql = "
                    SELECT COUNT(*) as total_records,
                           COUNT(DISTINCT name_store) as unique_stores,
                           AVG(CASE WHEN brand = 'AN' AND price_galon > 0 THEN price_galon END) as avg_an_galon,
                           AVG(CASE WHEN brand != 'AN' AND brand != '' AND price_galon > 0 THEN price_galon END) as avg_comp_galon
                    FROM cbp_raw
                    WHERE $whereSql
                ";
                $stmt = $pdo->prepare($kpiSql);
                $stmt->execute($params);
                $kpiRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                // 2. Trend Series for Line Chart
                $trendSql = "
                    SELECT 
                        CASE 
                            WHEN brand = 'AN' THEN 'AkzoNobel (Dulux)'
                            WHEN brand = 'Jotun' THEN 'Jotun'
                            WHEN brand IN ('Nippon', 'Nippon Paint') THEN 'Nippon Paint'
                            WHEN brand IN ('Avian', 'Aquaproof') THEN 'Avian / Aquaproof'
                            WHEN brand = 'Mowilex' THEN 'Mowilex'
                            ELSE 'Lainnya'
                        END as brand_group,
                        month,
                        AVG(price_galon) as avg_price
                    FROM cbp_raw
                    WHERE category IN ('Super Premium Interior', 'Premium Interior', 'Dulux Interior', 'Super Premium Exterior', 'Premium Exterior', 'Mass Interior', 'Washable Segment')
                      AND price_galon > 0
                      AND $whereSql
                    GROUP BY brand_group, month
                    ORDER BY brand_group, month
                ";
                $stmt = $pdo->prepare($trendSql);
                $stmt->execute($params);
                $trendRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $trendSeries = [
                    'AkzoNobel (Dulux)' => [],
                    'Jotun' => [],
                    'Nippon Paint' => [],
                    'Avian / Aquaproof' => [],
                    'Mowilex' => [],
                ];
                foreach ($trendRows as $tr) {
                    $bg = $tr['brand_group'];
                    if (isset($trendSeries[$bg])) {
                        $trendSeries[$bg][$tr['month']] = round((float)$tr['avg_price'], 0);
                    }
                }

                // Fill zero if month is missing in trend
                foreach ($trendSeries as $bg => &$tMonths) {
                    foreach ($months as $m => $mMeta) {
                        if (!isset($tMonths[$m])) {
                            $tMonths[$m] = 0;
                        }
                    }
                    ksort($tMonths);
                }
                unset($tMonths);

                // 3. Sections for Dashboard (1) & Dashboard (2)
                $sectionsConfig = [
                    'd1' => [
                        'super_premium_interior' => [
                            'title' => 'Super Premium Interior',
                            'category_query' => "category = 'Super Premium Interior'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Ambiance Emulsion',
                            'benchmark_label' => '100% = Ambiance Emulsion'
                        ],
                        'premium_interior' => [
                            'title' => 'Dulux Interior / Premium Interior',
                            'category_query' => "category IN ('Premium Interior', 'Dulux Interior')",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Pentalite',
                            'benchmark_label' => '100% = Pentalite'
                        ],
                        'washable' => [
                            'title' => 'Washable Segment / EasyClean',
                            'category_query' => "category = 'Washable Segment'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Easy Clean',
                            'benchmark_label' => '100% = EasyClean'
                        ],
                        'super_premium_exterior' => [
                            'title' => 'Super Premium Exterior',
                            'category_query' => "category = 'Super Premium Exterior'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Weathershield Powerflexx',
                            'benchmark_label' => '100% = Weathershield Powerflexx'
                        ],
                        'premium_exterior' => [
                            'title' => 'Premium Exterior',
                            'category_query' => "category = 'Premium Exterior'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Weathershield Core Dualshield',
                            'benchmark_label' => '100% = Weathershield Core'
                        ],
                        'mass_interior' => [
                            'title' => 'Mass Interior',
                            'category_query' => "category = 'Mass Interior'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Catylac Interior',
                            'benchmark_label' => '100% = Catylac Interior'
                        ],
                    ],
                    'd2' => [
                        'enamel' => [
                            'title' => 'Enamel (Cat Kayu & Besi)',
                            'category_query' => "category = 'Enamel'",
                            'metric' => 'price_tin',
                            'unit' => 'Tin (1 Liter / 1 Kg)',
                            'benchmark_product' => 'V-Gloss High Gloss',
                            'benchmark_label' => '100% = V-Gloss High Gloss'
                        ],
                        'waterproofing' => [
                            'title' => 'Waterproofing (Pelapis Anti Bocor)',
                            'category_query' => "category = 'Waterproofing'",
                            'metric' => 'price_galon',
                            'unit' => 'Galon (2.5L / 4-5Kg)',
                            'benchmark_product' => 'Aquashield',
                            'benchmark_label' => '100% = Aquashield'
                        ],
                    ]
                ];

                $dashboards = ['d1' => [], 'd2' => []];

                foreach (['d1', 'd2'] as $dKey) {
                    foreach ($sectionsConfig[$dKey] as $sKey => $cfg) {
                        $metricCol = $cfg['metric'];
                        $catQ = $cfg['category_query'];

                        $sqlSec = "
                            SELECT product, brand, month,
                                   AVG($metricCol) as avg_price,
                                   COUNT(*) as cnt
                            FROM cbp_raw
                            WHERE $catQ AND $metricCol > 0 AND $whereSql
                            GROUP BY product, brand, month
                            ORDER BY brand, product, month
                        ";

                        $stmt = $pdo->prepare($sqlSec);
                        $stmt->execute($params);
                        $secRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                        $prods = [];
                        foreach ($secRows as $r) {
                            $p = trim($r['product']);
                            if (empty($p)) continue;

                            if (!isset($prods[$p])) {
                                $b = trim($r['brand']);
                                if (empty($b)) {
                                    $isAn = (stripos($p, 'Ambiance') !== false || stripos($p, 'Dulux') !== false || stripos($p, 'Catylac') !== false || stripos($p, 'Weathershield') !== false || stripos($p, 'Pentalite') !== false || stripos($p, 'Easy Clean') !== false || stripos($p, 'V-Gloss') !== false || stripos($p, 'Aquashield') !== false);
                                    $b = $isAn ? 'AN' : 'Kompetitor';
                                }
                                $prods[$p] = [
                                    'product' => $p,
                                    'brand' => $b,
                                    'is_benchmark' => ($p === $cfg['benchmark_product']),
                                    'prices' => [],
                                    'indices' => [],
                                    'mom_growth' => [],
                                    'avg_price' => 0,
                                    'avg_index' => 0,
                                    'avg_mom' => null,
                                ];
                            }
                            $prods[$p]['prices'][$r['month']] = (float)$r['avg_price'];
                        }

                        // Determine Benchmark Prices
                        $bmPrices = $prods[$cfg['benchmark_product']]['prices'] ?? [];
                        if (empty($bmPrices)) {
                            foreach ($prods as $p => $info) {
                                if ($info['brand'] === 'AN' && !empty($info['prices'])) {
                                    $bmPrices = $info['prices'];
                                    $prods[$p]['is_benchmark'] = true;
                                    break;
                                }
                            }
                        }

                        // Calculate Indices, MoM Growth and Averages
                        foreach ($prods as $p => &$info) {
                            $validPrices = [];
                            $validIndices = [];
                            $validMoMs = [];
                            foreach ($months as $m => $mMeta) {
                                $price = $info['prices'][$m] ?? null;
                                $bmPrice = $bmPrices[$m] ?? null;
                                if ($price && $price > 0) {
                                    $validPrices[] = $price;
                                }
                                if ($price && $bmPrice && $bmPrice > 0) {
                                    $idx = ($price / $bmPrice) * 100;
                                    $info['indices'][$m] = $idx;
                                    $validIndices[] = $idx;
                                } else {
                                    $info['indices'][$m] = null;
                                }

                                // MoM Growth (%): (Current Month Price - Previous Month Price) / Previous Month Price * 100
                                $prevPrice = $info['prices'][$m - 1] ?? null;
                                if ($prevPrice && $prevPrice > 0 && $price && $price > 0) {
                                    $mom = (($price - $prevPrice) / $prevPrice) * 100;
                                    $info['mom_growth'][$m] = $mom;
                                    $validMoMs[] = $mom;
                                } else {
                                    $info['mom_growth'][$m] = null;
                                }
                            }
                            $info['avg_price'] = count($validPrices) > 0 ? (array_sum($validPrices) / count($validPrices)) : 0;
                            $info['avg_index'] = count($validIndices) > 0 ? (array_sum($validIndices) / count($validIndices)) : 0;
                            $info['avg_mom'] = count($validMoMs) > 0 ? (array_sum($validMoMs) / count($validMoMs)) : null;
                        }
                        unset($info);

                        // Sort products
                        uasort($prods, function($a, $b) {
                            if ($a['is_benchmark']) return -1;
                            if ($b['is_benchmark']) return 1;
                            if ($a['brand'] === 'AN' && $b['brand'] !== 'AN') return -1;
                            if ($a['brand'] !== 'AN' && $b['brand'] === 'AN') return 1;
                            return strcasecmp($a['product'], $b['product']);
                        });

                        $dashboards[$dKey][$sKey] = [
                            'title' => $cfg['title'],
                            'unit' => $cfg['unit'],
                            'benchmark_label' => $cfg['benchmark_label'],
                            'benchmark_product' => $cfg['benchmark_product'],
                            'products' => $prods
                        ];
                    }
                }

                $avgAn = (float)($kpiRow['avg_an_galon'] ?? 0);
                $avgComp = (float)($kpiRow['avg_comp_galon'] ?? 0);

                return [
                    'months' => $months,
                    'kpis' => [
                        'total_records' => (int)($kpiRow['total_records'] ?? 0),
                        'unique_stores' => (int)($kpiRow['unique_stores'] ?? 0),
                        'avg_an_galon' => $avgAn,
                        'avg_comp_galon' => $avgComp,
                        'ratio_index' => ($avgComp > 0) ? (($avgAn / $avgComp) * 100) : 100,
                    ],
                    'trend_series' => $trendSeries,
                    'dashboard1' => $dashboards['d1'],
                    'dashboard2' => $dashboards['d2']
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate CBP Dashboard: " . $e->getMessage());
                return null;
            }
        });

        if (!$aggData) {
            return null;
        }

        $months = $aggData['months'] ?? [];

        // Fetch Paginated Raw Data (Matching Excel Sheet 'Raw Data')
        try {
            $pdo = new \PDO("sqlite:" . $sqlitePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
            $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;

            $sMonth = max(1, min(12, (int)$startMonth));
            $eMonth = max(1, min(12, (int)$endMonth));
            if ($sMonth > $eMonth) {
                $tmp = $sMonth; $sMonth = $eMonth; $eMonth = $tmp;
            }

            $whereClauses = ["month BETWEEN ? AND ?"];
            $params = [$sMonth, $eMonth];

            if ($selectedRegion) {
                $whereClauses[] = "regional = ?";
                $params[] = $selectedRegion;
            }
            if ($selectedAreaName) {
                $whereClauses[] = "(UPPER(area) LIKE ? OR UPPER(rsm_area) LIKE ?)";
                $params[] = "%" . strtoupper($selectedAreaName) . "%";
                $params[] = "%" . strtoupper($selectedAreaName) . "%";
            }
            if ($selectedStoreName) {
                $whereClauses[] = "name_store LIKE ?";
                $params[] = "%$selectedStoreName%";
            }
            if ($search) {
                $whereClauses[] = "(product LIKE ? OR brand LIKE ? OR name_store LIKE ? OR sap_member LIKE ? OR sap_gab LIKE ? OR tl_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $whereSql = implode(" AND ", $whereClauses);

            // Ensure SQLite indexes exist for ultra-fast multi-month queries
            try {
                $pdo->exec("
                    CREATE INDEX IF NOT EXISTS idx_cbp_code ON cbp_raw(code);
                    CREATE INDEX IF NOT EXISTS idx_cbp_month_code ON cbp_raw(month, code);
                ");
            } catch (\Throwable $e) {}

            // Count distinct store + product items matching filter
            $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT code) FROM cbp_raw WHERE $whereSql");
            $countStmt->execute($params);
            $rawTotal = (int)$countStmt->fetchColumn();

            $rawOffset = ($rawPage - 1) * $rawPerPage;
            $rawSql = "
                SELECT code, regional, sap_member, sap_gab, name_store, tl_name, area, rsm_area, class, store_type, product, category, product_group
                FROM cbp_raw
                WHERE $whereSql
                GROUP BY code
                ORDER BY regional, area, name_store, product
                LIMIT $rawPerPage OFFSET $rawOffset
            ";
            $rawStmt = $pdo->prepare($rawSql);
            $rawStmt->execute($params);
            $rawRows = $rawStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch monthly prices for these 50 items across the filtered months
            $codes = array_column($rawRows, 'code');
            $activeMonthKeys = array_keys($months);

            if (!empty($codes) && !empty($activeMonthKeys)) {
                $codePlaceholders = implode(',', array_fill(0, count($codes), '?'));
                $monthPlaceholders = implode(',', array_fill(0, count($activeMonthKeys), '?'));

                $priceSql = "
                    SELECT code, month, trans_date,
                           price_tin, lowest_tin, reason_tin,
                           price_galon, lowest_galon, reason_galon,
                           price_pail, lowest_pail, reason_pail
                    FROM cbp_raw
                    WHERE code IN ($codePlaceholders) AND month IN ($monthPlaceholders)
                ";
                $priceStmt = $pdo->prepare($priceSql);
                $priceStmt->execute(array_merge($codes, $activeMonthKeys));
                $priceRows = $priceStmt->fetchAll(\PDO::FETCH_ASSOC);

                $pivoted = [];
                foreach ($priceRows as $pr) {
                    $pivoted[$pr['code']][$pr['month']] = $pr;
                }

                foreach ($rawRows as &$it) {
                    $it['monthly_prices'] = $pivoted[$it['code']] ?? [];
                }
                unset($it);
            }

            $aggData['raw_data'] = [
                'rows' => $rawRows,
                'total' => $rawTotal,
                'page' => $rawPage,
                'per_page' => $rawPerPage,
                'total_pages' => (int)ceil($rawTotal / $rawPerPage),
                'from' => $rawTotal > 0 ? ($rawOffset + 1) : 0,
                'to' => min($rawOffset + $rawPerPage, $rawTotal),
                'months' => $months
            ];
        } catch (\Throwable $e) {
            \Log::error("Failed to query CBP Raw Data: " . $e->getMessage());
            $aggData['raw_data'] = [
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $rawPerPage,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0
            ];
        }

        return $aggData;
    }
}
