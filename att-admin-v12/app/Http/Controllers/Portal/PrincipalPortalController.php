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
     * Helper to retrieve active report templates for tenant principal sorted by requested portal menu order
     */
    protected function getActiveTemplates(array $scopedPrincipalIds, ?Principal $tenantPrincipal = null)
    {
        $templates = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->where('report_templates.is_active', true)
            ->with('fields')
            ->get();

        return $templates->sort(function ($a, $b) {
            $orderA = $this->getTemplateMenuSortOrder($a);
            $orderB = $this->getTemplateMenuSortOrder($b);

            if ($orderA === $orderB) {
                return $a->id <=> $b->id;
            }

            return $orderA <=> $orderB;
        })->values();
    }

    /**
     * Get sort priority weight for report template in portal menu navigation.
     * Requested Order:
     * 1. Offtake
     * 2. Stok End
     * 3. OOS
     * 4. CBP
     * 5. Daily Maintenance
     * 6. Database Pelanggan
     */
    protected function getTemplateMenuSortOrder($template): int
    {
        $code = strtoupper($template->code ?? '');
        $title = strtolower($template->title ?? '');
        $category = strtolower($template->category ?? '');

        // 1. Offtake
        if (str_contains($code, 'OFFTAKE') || str_contains($title, 'offtake') || $category === 'offtake') {
            return 10;
        }

        // 2. Stok End (must check before generic stock / oos)
        if (
            str_contains($code, 'STOCK-END') || str_contains($code, 'STOCK_END') ||
            str_contains($code, 'STOK-END') || str_contains($code, 'STOK_END') ||
            str_contains($title, 'stock end') || str_contains($title, 'stok end') ||
            str_contains($title, 'stock ending') || str_contains($title, 'stok ending')
        ) {
            return 20;
        }

        // 3. OOS (Out of Stock)
        if (
            str_contains($code, 'OOS') ||
            str_contains($title, 'oos') ||
            str_contains($title, 'out of stock') ||
            str_contains($title, 'barang kosong') ||
            str_contains($title, 'stok kosong')
        ) {
            return 30;
        }

        // 4. CBP (Pricing / Cek Harga)
        if (
            str_contains($code, 'CBP') ||
            str_contains($code, 'PRICING') ||
            str_contains($title, 'cbp') ||
            str_contains($title, 'pricing') ||
            $category === 'pricing' ||
            $category === 'price'
        ) {
            return 40;
        }

        // 5. Daily Maintenance
        if (
            str_contains($code, 'DAILY-MAINTENANCE') ||
            str_contains($code, 'DAILY_MAINTENANCE') ||
            str_contains($code, 'MAINTENANCE') ||
            str_contains($title, 'daily maintenance') ||
            str_contains($title, 'daily maintance') ||
            str_contains($title, 'perawatan harian') ||
            str_contains($title, 'maintenance')
        ) {
            return 50;
        }

        // 6. Database Pelanggan
        if (
            str_contains($code, 'DATABASE-PELANGGAN') ||
            str_contains($code, 'DATA-PELANGGAN') ||
            str_contains($code, 'DATABASE_PELANGGAN') ||
            str_contains($code, 'DATA_PELANGGAN') ||
            str_contains($title, 'database pelanggan') ||
            str_contains($title, 'data pelanggan') ||
            str_contains($title, 'konsumen') ||
            str_contains($title, 'pelanggan')
        ) {
            return 60;
        }

        // Other generic categories
        if (str_contains($code, 'TRAFIK') || str_contains($title, 'trafik')) {
            return 70;
        }

        if (str_contains($code, 'MITRA') || str_contains($title, 'mitra') || str_contains($title, 'painter')) {
            return 80;
        }

        if (str_contains($code, 'STOCK') || str_contains($code, 'STOK') || str_contains($title, 'stok') || str_contains($title, 'stock') || $category === 'stock') {
            return 85;
        }

        return 100;
    }

    /**
     * Standard list of 11 RSM areas for Dulux / ICI Paints
     */
    protected function getDuluxStandardRsmList(): array
    {
        return [
            'Bali Nusra',
            'Central Sumatera',
            'East Java',
            'Greater Jakarta',
            'Kalimantan',
            'North Central Java',
            'North Sumatera',
            'South Central Java',
            'South Sumatera',
            'Sulawesi',
            'West Java',
        ];
    }

    /**
     * Complete Map of Indonesian Dulux Sales Area / City to RSM Area
     */
    protected function getDuluxAreaToRsmMap(): array
    {
        return [
            'ACEH' => 'North Sumatera',
            'MEDAN' => 'North Sumatera',
            'BATAM' => 'Central Sumatera',
            'PADANG' => 'Central Sumatera',
            'PEKANBARU' => 'Central Sumatera',
            'LAMPUNG' => 'South Sumatera',
            'PALEMBANG' => 'South Sumatera',
            'JAMBI' => 'South Sumatera',
            'BENGKULU' => 'South Sumatera',
            'BALIKPAPAN' => 'Kalimantan',
            'SAMARINDA' => 'Kalimantan',
            'BONTANG' => 'Kalimantan',
            'BANJARMASIN' => 'Kalimantan',
            'PONTIANAK' => 'Kalimantan',
            'KENDARI' => 'Sulawesi',
            'MAKASSAR' => 'Sulawesi',
            'MANADO' => 'Sulawesi',
            'PALU' => 'Sulawesi',
            'MALUKU' => 'Sulawesi',
            'PAPUA' => 'Sulawesi',
            'CIBUBUR' => 'Greater Jakarta',
            'GARUT' => 'West Java',
            'BANDUNG' => 'West Java',
            'CIREBON' => 'West Java',
            'TASIKMALAYA' => 'West Java',
            'BOGOR' => 'Greater Jakarta',
            'BEKASI' => 'Greater Jakarta',
            'DEPOK' => 'Greater Jakarta',
            'TANGERANG' => 'Greater Jakarta',
            'JAKARTA BARAT' => 'Greater Jakarta',
            'JAKARTA PUSAT' => 'Greater Jakarta',
            'JAKARTA UTARA' => 'Greater Jakarta',
            'JAKARTA TIMUR' => 'Greater Jakarta',
            'JAKARTA SELATAN' => 'Greater Jakarta',
            'MADIUN' => 'East Java',
            'SURABAYA' => 'East Java',
            'MALANG' => 'East Java',
            'KEDIRI' => 'East Java',
            'BANYUWANGI' => 'East Java',
            'JEMBER' => 'Bali Nusra',
            'BALI' => 'Bali Nusra',
            'LOMBOK' => 'Bali Nusra',
            'KUPANG' => 'Bali Nusra',
            'SEMARANG' => 'North Central Java',
            'TEGAL' => 'North Central Java',
            'PEKALONGAN' => 'North Central Java',
            'KUDUS' => 'North Central Java',
            'SOLO' => 'South Central Java',
            'YOGYAKARTA' => 'South Central Java',
            'PURWOKERTO' => 'South Central Java',
            'MAGELANG' => 'South Central Java',
            'CENTRAL JAVA' => 'Central Java',
        ];
    }

    /**
     * Database variations of RSM Area names in legacy tables
     */
    protected function getRsmQueryVariants(string $rsm): array
    {
        $r = trim($rsm);
        $map = [
            'Bali Nusra' => ['Bali Nusra', 'Bali Nusa Puma', 'BALI NUSA PUMA', 'BALI NUSRA', 'Bali'],
            'Central Sumatera' => ['Central Sumatera', 'Central Sumatra', 'CENTRAL SUMATERA', 'CENTRAL SUMATRA'],
            'North Sumatera' => ['North Sumatera', 'North Sumatra', 'NORTH SUMATERA', 'NORTH SUMATRA'],
            'South Sumatera' => ['South Sumatera', 'South Sumatra', 'SOUTH SUMATERA', 'SOUTH SUMATRA'],
            'West Java' => ['West Java', 'Jawa Barat', 'WEST JAVA', 'JAWA BARAT'],
            'East Java' => ['East Java', 'Jawa Timur', 'EAST JAVA', 'JAWA TIMUR'],
            'Kalimantan' => ['Kalimantan', 'KALIMANTAN'],
            'Sulawesi' => ['Sulawesi', 'SULAWESI'],
            'Greater Jakarta' => ['Greater Jakarta', 'GREATER JAKARTA'],
            'North Central Java' => ['North Central Java', 'NORTH CENTRAL JAVA'],
            'South Central Java' => ['South Central Java', 'SOUTH CENTRAL JAVA'],
        ];
        return $map[$r] ?? [$r];
    }

    /**
     * Map any Sales Area / Branch / City name to standardized RSM Area
     */
    protected function mapAreaToRsm(string $areaName, ?string $fallbackRsm = null): string
    {
        $clean = strtoupper(trim($areaName));
        $map = $this->getDuluxAreaToRsmMap();
        if (isset($map[$clean])) {
            return $map[$clean];
        }
        foreach ($map as $key => $rsm) {
            if (str_contains($clean, $key)) {
                return $rsm;
            }
        }
        return $fallbackRsm ?: 'East Java';
    }

    /**
     * Reusable Eloquent query for live mobile/admin submissions matching active filters
     */
    protected function getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion = null, $selectedAreaId = null, $selectedLocationId = null, $search = null)
    {
        $query = ReportSubmission::where('report_submissions.report_template_id', $template->id)
            ->whereBetween('report_submissions.submitted_at', [$startDate, $endDate])
            ->with([
                'employee.branch',
                'employee.supervisor',
                'workLocation.branch',
                'values.formField'
            ]);

        if ($selectedRegion) {
            $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
            $query->where(function($q) use ($rsmVariants, $selectedRegion) {
                $q->whereHas('workLocation', function($wq) use ($rsmVariants, $selectedRegion) {
                    $wq->whereIn('region', $rsmVariants)->orWhere('region', $selectedRegion);
                })->orWhereHas('workLocation.branch', function($bq) use ($rsmVariants, $selectedRegion) {
                    $bq->whereIn('region', $rsmVariants)->orWhere('region', $selectedRegion);
                });
            });
        }

        if ($selectedAreaId) {
            if (is_numeric($selectedAreaId)) {
                $query->whereHas('workLocation', fn($w) => $w->where('branch_id', $selectedAreaId));
            } else {
                $query->whereHas('workLocation.branch', fn($b) => $b->where('name', 'ILIKE', "%{$selectedAreaId}%"));
            }
        }

        if ($selectedLocationId) {
            if (is_numeric($selectedLocationId)) {
                $query->where('work_location_id', $selectedLocationId);
            } else {
                $query->whereHas('workLocation', fn($w) => $w->where('name', 'ILIKE', "%{$selectedLocationId}%"));
            }
        }

        if ($search) {
            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($subQ) use ($search, $likeOp) {
                $subQ->where('submission_code', $likeOp, "%{$search}%")
                    ->orWhereIn('report_submissions.employee_id', function ($eQ) use ($search, $likeOp) {
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

        // Jika tidak ditentukan di request, defaultkan ke bulan & tahun berjalan
        if (!$hasExplicitDate || $startMonth <= 0 || $startYear <= 0) {
            $startMonth = (int) Carbon::now()->month;
            $startYear  = (int) Carbon::now()->year;
            $endMonth   = (int) ($request->query('end_month') ?? Carbon::now()->month);
            $endYear    = (int) ($request->query('end_year') ?? Carbon::now()->year);
        } else {
            $endMonth   = (int) ($request->query('end_month') ?? $startMonth);
            $endYear    = (int) ($request->query('end_year') ?? $startYear);
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
            $selectedYear = (int)$request->query('year', (int)$request->query('start_year', Carbon::now()->year));
            if ($selectedYear <= 0) $selectedYear = 2026;

            $sqlitePath = storage_path("app/dulux_data/cbp_{$selectedYear}.sqlite");
            if (!file_exists($sqlitePath) && $selectedYear !== 2026 && file_exists(storage_path('app/dulux_data/cbp_2026.sqlite'))) {
                $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
            }

            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Areas directly from cbp_raw with standardized RSM info
            $areas = Cache::remember("cbp_filter_areas_v13_{$selectedYear}", 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT COALESCE(NULLIF(rsm_area,''), regional) as rsm, MIN(area) as area_name FROM cbp_raw WHERE area IS NOT NULL AND area != '' GROUP BY UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $cleanA = strtoupper(trim($a['area_name']));
                        $rsm = $areaToRsm[$cleanA] ?? $a['rsm'];
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $rsm
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from cbp_raw with RSM & area info
            $workLocations = Cache::remember("cbp_filter_stores_v13_{$selectedYear}", 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT COALESCE(NULLIF(rsm_area,''), regional) as rsm, MIN(area) as area, name_store FROM cbp_raw WHERE name_store IS NOT NULL GROUP BY name_store ORDER BY name_store ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? $s['rsm'];
                        $result[] = [
                            'id' => $s['name_store'],
                            'name' => $s['name_store'],
                            'region' => $rsm,
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
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
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
                'liveSubmissionsCount',
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
        $isStockReport   = ($template->code === 'RPT-DULUX-STOCK-END');
        $isOosReport     = in_array($template->code, ['RPT-DULUX-OOS-SSO', 'RPT-DULUX-OOS-LSO']) || str_contains($template->code, 'OOS');
        $isDailyMaintenanceReport = ($template->code === 'RPT-DULUX-DAILY-MAINTENANCE' || str_contains($template->code, 'DAILY-MAINTENANCE'));
        $isCustomerDbReport       = ($template->code === 'RPT-DULUX-DATABASE-PELANGGAN' || str_contains($template->code, 'PELANGGAN'));

        // --- Stock End Custom Handling (Pivotable Store Volume, SCM / Summ & Raw Submissions from stock_YYYY.sqlite) ---
        if ($isStockReport) {
            $selectedYear = (int)$endYear;
            $sqlitePath = storage_path("app/dulux_data/stock_{$selectedYear}.sqlite");
            $gzPath     = storage_path("app/dulux_data/stock_{$selectedYear}.sqlite.gz");
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
                        \Log::error("Auto-extraction of stock_{$selectedYear}.sqlite.gz failed: " . $e->getMessage());
                    }
                } elseif (!file_exists($sqlitePath)) {
                    $sqlitePath = storage_path('app/dulux_data/stock_2026.sqlite');
                    $gzPath     = storage_path('app/dulux_data/stock_2026.sqlite.gz');
                }
            }

            // Standardized RSM List for Stock
            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Areas directly from stock_raw with standardized RSM info
            $areas = Cache::remember('stock_filter_areas_v4_' . $selectedYear, 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT COALESCE(NULLIF(rsm_area,''), region) as rsm, MIN(area) as area_name FROM stock_raw WHERE area IS NOT NULL AND area != '' GROUP BY UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $cleanA = strtoupper(trim($a['area_name']));
                        $rsm = $areaToRsm[$cleanA] ?? $a['rsm'];
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $rsm
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from stock_raw with RSM info
            $workLocations = Cache::remember('stock_filter_stores_v4_' . $selectedYear, 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT COALESCE(NULLIF(rsm_area,''), region) as rsm, MIN(area) as area, sap, store_name FROM stock_raw WHERE store_name IS NOT NULL AND store_name != '' GROUP BY store_name ORDER BY store_name ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? $s['rsm'];
                        $result[] = [
                            'id' => $s['store_name'],
                            'name' => $s['store_name'],
                            'region' => $rsm,
                            'area' => $s['area'],
                            'sap' => $s['sap']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            $selectedBrand = strtoupper(trim((string)$request->query('brand', 'ALL')));
            if (!in_array($selectedBrand, ['ALL', 'DULUX', 'CATYLAC'])) {
                $selectedBrand = 'ALL';
            }

            $stockPage = max(1, (int)$request->query('page', 1));
            $summPage  = max(1, (int)$request->query('summ_page', 1));
            $rawPage   = max(1, (int)$request->query('raw_page', 1));
            $activeTab = $request->query('tab', 'monthly');

            $stockData = $this->calculateStockDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $selectedBrand,
                $search,
                $stockPage,
                $summPage,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = 0;
            $uniqueStores = 0;
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
            $dashboardConfig = [];
            $widgetResults = [];
            $isYtdReport = false;
            $monthlyCompareData = $this->calculateStockMonthlyCompareData(
                $template,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $selectedBrand,
                $search
            );

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
                'selectedBrand',
                'regions',
                'areas',
                'workLocations',
                'setting',
                'dashboardConfig',
                'widgetResults',
                'isYtdReport',
                'isCbpReport',
                'isOfftakeReport',
                'isStockReport',
                'stockData',
                'monthlyCompareData',
                'activeTab'
            ));
        }

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

            // Standardized RSM List for Offtake
            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Areas directly from offtake_raw mapped to RSM
            $areas = Cache::remember('offtake_filter_areas_v3', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT MIN(area) as area_name FROM offtake_raw WHERE area IS NOT NULL AND area != '' GROUP BY UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $cleanA = strtoupper(trim($a['area_name']));
                        $rsm = $areaToRsm[$cleanA] ?? '';
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $rsm
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from offtake_raw mapped to RSM
            $workLocations = Cache::remember('offtake_filter_stores_v3', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT MIN(area) as area, sap, name_store FROM offtake_raw WHERE name_store IS NOT NULL AND name_store != '' GROUP BY name_store ORDER BY name_store ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? '';
                        $result[] = [
                            'id' => $s['name_store'],
                            'name' => $s['name_store'],
                            'region' => $rsm,
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
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
            $dashboardConfig = [];
            $widgetResults = [];
            $isYtdReport = true;
            $ytdData = $this->calculateOfftakeYtdData(
                $template,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $search
            );

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

        // --- Out of Stock (OOS) Custom Handling (Summary, Weekly Pivot & Raw Submissions from oos_2026.sqlite) ---
        if ($isOosReport) {
            $sqlitePath = storage_path('app/dulux_data/oos_2026.sqlite');
            $gzPath     = storage_path('app/dulux_data/oos_2026.sqlite.gz');

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
                        \Log::error("Auto-extraction of oos_2026.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            // Regions directly from oos_raw (standardized RSM list)
            // Standardized RSM List for OOS
            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Areas directly from oos_raw with standardized RSM info
            $areas = Cache::remember('oos_filter_areas_v3', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT COALESCE(NULLIF(rsm_area,''), region) as rsm, MIN(area) as area_name FROM oos_raw WHERE area IS NOT NULL AND area != '' GROUP BY UPPER(TRIM(area)) ORDER BY area_name ASC");
                    $rawAreas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawAreas as $a) {
                        $cleanA = strtoupper(trim($a['area_name']));
                        $rsm = $areaToRsm[$cleanA] ?? $a['rsm'];
                        $result[] = [
                            'id' => $a['area_name'],
                            'name' => ucwords(strtolower($a['area_name'])),
                            'region' => $rsm
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Stores directly from oos_raw with RSM info
            $workLocations = Cache::remember('oos_filter_stores_v3', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT COALESCE(NULLIF(rsm_area,''), region) as rsm, MIN(area) as area, sap, store_name FROM oos_raw WHERE store_name IS NOT NULL AND store_name != '' GROUP BY store_name ORDER BY store_name ASC");
                    $rawStores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $result = [];
                    foreach ($rawStores as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? $s['rsm'];
                        $result[] = [
                            'id' => $s['store_name'],
                            'name' => $s['store_name'],
                            'region' => $rsm,
                            'area' => $s['area'],
                            'sap' => $s['sap']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            $selectedChannel = $request->query('channel', '');
            $showNoOos       = (bool)$request->query('show_no_oos', 0);
            $weeklyPage      = max(1, (int)$request->query('weekly_page', 1));
            $rawPage         = max(1, (int)$request->query('raw_page', 1));
            $activeTab       = $request->query('tab', 'summary');

            $oosData = $this->calculateOosDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $selectedChannel,
                $showNoOos,
                $search,
                $weeklyPage,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = 0;
            $uniqueStores = 0;
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
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
                'selectedChannel',
                'showNoOos',
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
                'isStockReport',
                'isOosReport',
                'oosData',
                'activeTab'
            ));
        }

        // --- Daily Maintenance Custom Handling (Summary, Store Matrix, Raw Data from daily_maintenance.sqlite) ---
        if ($isDailyMaintenanceReport) {
            $sqlitePath = storage_path('app/dulux_data/daily_maintenance.sqlite');
            $gzPath     = storage_path('app/dulux_data/daily_maintenance.sqlite.gz');

            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                        \Log::error("Auto-extraction of daily_maintenance.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            // Standardized RSM List for Daily Maintenance
            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Extract distinct areas directly from dm_raw mapped to RSM
            $areas = Cache::remember('dm_filter_areas_v4', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT area, rsm_area FROM dm_raw WHERE area IS NOT NULL AND area != '' ORDER BY area");
                    $results = [];
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $a) {
                        $cleanA = strtoupper(trim($a['area']));
                        $rsm = $areaToRsm[$cleanA] ?? $a['rsm_area'];
                        $results[] = [
                            'id' => $a['area'],
                            'name' => $a['area'],
                            'region' => $rsm
                        ];
                    }
                    return $results;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Extract distinct stores directly from dm_raw mapped to RSM
            $workLocations = Cache::remember('dm_filter_stores_v4', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT store_name, rsm_area, area, sap_code FROM dm_raw WHERE store_name IS NOT NULL AND store_name != '' ORDER BY store_name");
                    $result = [];
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? $s['rsm_area'];
                        $result[] = [
                            'id' => $s['store_name'],
                            'name' => $s['store_name'],
                            'region' => $rsm,
                            'area' => $s['area'],
                            'sap' => $s['sap_code']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Extract Machine Types
            $machineTypes = Cache::remember('dm_filter_mtypes_v3', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT machine_type FROM dm_raw WHERE machine_type IS NOT NULL AND machine_type != '' ORDER BY machine_type");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['D200', 'Discovery', 'Manual', 'X-Smart', 'Xprotint', 'Other'];
                }
            });

            // Extract Categories
            $categories = Cache::remember('dm_filter_cats_v3', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT category FROM dm_raw WHERE category IS NOT NULL AND category != '' ORDER BY category");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['Bluestore', 'LSO', 'MTI', 'SSO'];
                }
            });

            $selectedMachineType = $request->query('machine_type', '');
            $selectedCategory    = $request->query('category', '');
            $storePage           = max(1, (int)$request->query('store_page', 1));
            $rawPage             = max(1, (int)$request->query('raw_page', 1));
            $activeTab           = $request->query('tab', 'summary');

            $dailyMaintenanceData = $this->calculateDailyMaintenanceDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $selectedMachineType,
                $selectedCategory,
                $search,
                $storePage,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = 0;
            $uniqueStores = 0;
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
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
                'selectedMachineType',
                'selectedCategory',
                'regions',
                'areas',
                'workLocations',
                'machineTypes',
                'categories',
                'setting',
                'dashboardConfig',
                'widgetResults',
                'isYtdReport',
                'ytdData',
                'isCbpReport',
                'isOfftakeReport',
                'isStockReport',
                'isOosReport',
                'isDailyMaintenanceReport',
                'dailyMaintenanceData',
                'activeTab'
            ));
        }

        // --- Customer Database Custom Handling (Consumer Insights, Store Analytics & Raw Data from customer_db.sqlite) ---
        if ($isCustomerDbReport) {
            $sqlitePath = storage_path('app/dulux_data/customer_db.sqlite');
            $gzPath     = storage_path('app/dulux_data/customer_db.sqlite.gz');

            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 500000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                        \Log::error("Auto-extraction of customer_db.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            // Extract distinct regions directly from cust_raw
            // Standardized RSM List for Customer DB
            $regions = $this->getDuluxStandardRsmList();
            $areaToRsm = $this->getDuluxAreaToRsmMap();

            // Extract distinct areas directly from cust_raw mapped to RSM
            $areas = Cache::remember('cust_filter_areas_v2', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT area, rsm_area FROM cust_raw WHERE area IS NOT NULL AND area != '' ORDER BY area");
                    $results = [];
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $a) {
                        $cleanA = strtoupper(trim($a['area']));
                        $rsm = $areaToRsm[$cleanA] ?? $a['rsm_area'];
                        $results[] = [
                            'id' => $a['area'],
                            'name' => $a['area'],
                            'region' => $rsm
                        ];
                    }
                    return $results;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Extract distinct stores directly from cust_raw mapped to RSM
            $workLocations = Cache::remember('cust_filter_stores_v2', 3600, function() use ($sqlitePath, $areaToRsm) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT store_name, rsm_area, area, sap_code FROM cust_raw WHERE store_name IS NOT NULL AND store_name != '' ORDER BY store_name");
                    $result = [];
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $s) {
                        $cleanA = strtoupper(trim($s['area'] ?? ''));
                        $rsm = $areaToRsm[$cleanA] ?? $s['rsm_area'];
                        $result[] = [
                            'id' => $s['store_name'],
                            'name' => $s['store_name'],
                            'region' => $rsm,
                            'area' => $s['area'],
                            'sap' => $s['sap_code']
                        ];
                    }
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            });

            // Extract Customer Types
            $customerTypes = Cache::remember('cust_filter_types_v1', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT tipe_pelanggan FROM cust_raw WHERE tipe_pelanggan IS NOT NULL AND tipe_pelanggan != '' ORDER BY tipe_pelanggan");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['Kontraktor', 'Mitra Dulux', 'Pemilik Rumah', 'Tukang Cat & Bangunan'];
                }
            });

            // Extract Brands (Dicari / Dibeli)
            $brandsList = Cache::remember('cust_filter_brands_v1', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT brand_dicari FROM cust_raw WHERE brand_dicari IS NOT NULL AND brand_dicari != '' ORDER BY brand_dicari LIMIT 30");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['Dulux', 'Catylac', 'Jotun', 'Nippon', 'Vinilex', 'Mowilex', 'No drop', 'Propan'];
                }
            });

            // Extract Reasons
            $reasonsList = Cache::remember('cust_filter_reasons_v1', 3600, function() use ($sqlitePath) {
                try {
                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $stmt = $pdo->query("SELECT DISTINCT alasan FROM cust_raw WHERE alasan IS NOT NULL AND alasan != '' ORDER BY alasan");
                    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } catch (\Throwable $e) {
                    return ['Rekomendasi DC', 'Kualitasnya baik', 'Harga Terjangkau', 'Merk terkenal', 'Rekomendasi Painter/Kontraktor', 'Rekomendasi Toko'];
                }
            });

            $selectedCustomerType = $request->query('cust_type', '');
            $selectedBrand        = $request->query('brand', '');
            $selectedReason       = $request->query('reason', '');
            $topStorePage         = max(1, (int)$request->query('store_page', 1));
            $rawPage              = max(1, (int)$request->query('raw_page', 1));
            $activeTab            = $request->query('tab', 'insights');

            $customerDbData = $this->calculateCustomerDbDashboardData(
                $template,
                $startMonth,
                $startYear,
                $endMonth,
                $endYear,
                $selectedRegion,
                $selectedAreaId,
                $selectedLocationId,
                $selectedCustomerType,
                $selectedBrand,
                $selectedReason,
                $search,
                $topStorePage,
                $rawPage,
                50
            );

            $totalTemplateSubmissions = 0;
            $uniqueStores = 0;
            $submissions = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
                ->orderBy('submitted_at', 'desc')
                ->paginate(20);
            $liveSubmissionsCount = $submissions->total();
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
                'selectedCustomerType',
                'selectedBrand',
                'selectedReason',
                'regions',
                'areas',
                'workLocations',
                'customerTypes',
                'brandsList',
                'reasonsList',
                'setting',
                'dashboardConfig',
                'widgetResults',
                'isYtdReport',
                'ytdData',
                'isCbpReport',
                'isOfftakeReport',
                'isStockReport',
                'isOosReport',
                'isDailyMaintenanceReport',
                'isCustomerDbReport',
                'customerDbData',
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
        $subLocDataRaw = Cache::remember("rep_filter_locs_v4_{$template->id}", 600, function() use ($template) {
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
                ->get()
                ->toArray();
        });
        $subLocData = collect($subLocDataRaw);

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
                        'brand' => 'Total Akzonobel',
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
            $selectedYear = $endYear ?: $startYear ?: (int)($request->query('year') ?? 2026);
            if ($selectedYear <= 0) $selectedYear = 2026;

            $sqlitePath = storage_path("app/dulux_data/cbp_{$selectedYear}.sqlite");
            if (!file_exists($sqlitePath) && $selectedYear !== 2026 && file_exists(storage_path('app/dulux_data/cbp_2026.sqlite'))) {
                $sqlitePath = storage_path('app/dulux_data/cbp_2026.sqlite');
            }

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
                        $exportMonths[$m] = ($monthNames[$m] ?? "Bln $m") . ' ' . $endYear;
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
                        $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                        $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                        $whereClauses[] = "(rsm_area IN ($inPlaceholders) OR regional = ?)";
                        foreach ($rsmVariants as $rv) {
                            $params[] = $rv;
                        }
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
                            $pTin = (!empty($mp['price_tin']) && $mp['price_tin'] > 0) ? $mp['price_tin'] : (!empty($mp['lowest_tin']) ? $mp['lowest_tin'] : '');
                            $lTin = (!empty($mp['lowest_tin']) && $mp['lowest_tin'] > 0) ? $mp['lowest_tin'] : $pTin;
                            $row[] = $pTin;
                            $row[] = $lTin;
                            $row[] = $mp['reason_tin'] ?? '';

                            $pGalon = (!empty($mp['price_galon']) && $mp['price_galon'] > 0) ? $mp['price_galon'] : (!empty($mp['lowest_galon']) ? $mp['lowest_galon'] : '');
                            $lGalon = (!empty($mp['lowest_galon']) && $mp['lowest_galon'] > 0) ? $mp['lowest_galon'] : $pGalon;
                            $row[] = $pGalon;
                            $row[] = $lGalon;
                            $row[] = $mp['reason_galon'] ?? '';

                            $pPail = (!empty($mp['price_pail']) && $mp['price_pail'] > 0) ? $mp['price_pail'] : (!empty($mp['lowest_pail']) ? $mp['lowest_pail'] : '');
                            $lPail = (!empty($mp['lowest_pail']) && $mp['lowest_pail'] > 0) ? $mp['lowest_pail'] : $pPail;
                            $row[] = $pPail;
                            $row[] = $lPail;
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
                        $exportMonths[$m] = $monthNames[$m] . ' ' . $endYear;
                    }

                    $where = ["month BETWEEN ? AND ?"];
                    $params = [$sMonth, $eMonth];

                    if ($selectedRegion) {
                        $areaToRsm = $this->getDuluxAreaToRsmMap();
                        $matchingAreas = [];
                        foreach ($areaToRsm as $aName => $rsmName) {
                            if (strcasecmp($rsmName, $selectedRegion) === 0) {
                                $matchingAreas[] = $aName;
                            }
                        }
                        if (!empty($matchingAreas)) {
                            $placeholders = implode(',', array_fill(0, count($matchingAreas), '?'));
                            $where[] = "(UPPER(TRIM(area)) IN ($placeholders) OR region = ?)";
                            foreach ($matchingAreas as $ma) {
                                $params[] = $ma;
                            }
                            $params[] = $selectedRegion;
                        } else {
                            $where[] = "region = ?";
                            $params[] = $selectedRegion;
                        }
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

        // Custom Streamed CSV Export for Dulux Stock End Report (Pivotable, Summ SCM, Raw Submissions)
        if ($template->code === 'RPT-DULUX-STOCK-END') {
            $sqlitePath = storage_path('app/dulux_data/stock_2026.sqlite');
            $gzPath     = storage_path('app/dulux_data/stock_2026.sqlite.gz');
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
                        \Log::error("Auto-extraction of stock_2026.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            if (file_exists($sqlitePath)) {
                $exportType = $request->query('export_type', 'stock_pivot');
                $selectedBrand = strtoupper(trim((string)$request->query('brand', 'ALL')));
                if (!in_array($selectedBrand, ['ALL', 'DULUX', 'CATYLAC'])) {
                    $selectedBrand = 'ALL';
                }
                $brandSuffix = $selectedBrand !== 'ALL' ? "-{$selectedBrand}" : '';

                $filename = match($exportType) {
                    'stock_raw' => "raw-data-stock{$brandSuffix}-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv",
                    'stock_summ' => "ringkasan-scm-stock{$brandSuffix}-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv",
                    'stock_monthly_compare' => "perbandingan-tren-harian-stock{$brandSuffix}-{$endYear}_{$endMonth}.csv",
                    'stock_ytd_stores' => "ytd-stock-stores{$brandSuffix}-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv",
                    default => "rekap-volume-stock-toko{$brandSuffix}-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv"
                };

                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0',
                ];

                $callback = function () use ($sqlitePath, $startMonth, $endMonth, $startYear, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedBrand, $request, $exportType) {
                    $handle = fopen('php://output', 'w');
                    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                    $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;
                    $search = $request->query('q');

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    if ($sMonth > $eMonth) {
                        $tmp = $sMonth; $sMonth = $eMonth; $eMonth = $tmp;
                    }

                    $where = ["month BETWEEN ? AND ?"];
                    $params = [$sMonth, $eMonth];

                    if ($selectedRegion) {
                        $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                        $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                        $where[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                        foreach ($rsmVariants as $rv) {
                            $params[] = $rv;
                        }
                        $params[] = $selectedRegion;
                    }
                    if ($selectedAreaName) {
                        $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                        $params[] = $selectedAreaName;
                    }
                    if ($selectedStoreName) {
                        $where[] = "store_name = ?";
                        $params[] = $selectedStoreName;
                    }
                    if ($selectedBrand === 'DULUX') {
                        $where[] = "brand = 'Dulux'";
                    } elseif ($selectedBrand === 'CATYLAC') {
                        $where[] = "(brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%')";
                    }
                    if ($search) {
                        $where[] = "(store_name LIKE ? OR sap LIKE ? OR brand LIKE ? OR produk LIKE ?)";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                    }
                    $whereSql = implode(' AND ', $where);

                    if ($exportType === 'stock_monthly_compare') {
                        $p25 = storage_path('app/dulux_data/stock_2025.sqlite');
                        $has2025 = file_exists($p25);
                        if ($has2025) {
                            try { $pdo->exec("ATTACH DATABASE '{$p25}' AS db25"); } catch (\Throwable $e) { $has2025 = false; }
                        }

                        $headerRow = [
                            'Tanggal', 'Hari Ke', 'Bulan / Tahun', 'Volume ' . $endYear . ' (L)', 'Volume ' . ($endYear - 1) . ' (L)', 'Selisih / Delta (L)', 'Pertumbuhan YoY (%)'
                        ];
                        fputcsv($handle, $headerRow);

                        $dayExprCy = "CAST(COALESCE(NULLIF(substr(submission_date, 9, 2), ''), NULLIF(substr(tgl_catat, 9, 2), ''), '20') AS INTEGER)";
                        
                        $whereCompareCy = ["month = ?"];
                        $paramsCompareCy = [$endMonth];
                        if ($selectedRegion) {
                            $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                            $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                            $whereCompareCy[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                            foreach ($rsmVariants as $rv) {
                                $paramsCompareCy[] = $rv;
                            }
                            $paramsCompareCy[] = $selectedRegion;
                        }
                        if ($selectedAreaName) { $whereCompareCy[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))"; $paramsCompareCy[] = $selectedAreaName; }
                        if ($selectedStoreName) { $whereCompareCy[] = "store_name = ?"; $paramsCompareCy[] = $selectedStoreName; }
                        if ($selectedBrand === 'DULUX') { $whereCompareCy[] = "brand = 'Dulux'"; }
                        elseif ($selectedBrand === 'CATYLAC') { $whereCompareCy[] = "(brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%')"; }
                        if ($search) {
                            $whereCompareCy[] = "(store_name LIKE ? OR sap LIKE ? OR brand LIKE ? OR produk LIKE ?)";
                            $paramsCompareCy[] = "%{$search}%"; $paramsCompareCy[] = "%{$search}%"; $paramsCompareCy[] = "%{$search}%"; $paramsCompareCy[] = "%{$search}%";
                        }
                        $whereCompareCySql = implode(' AND ', $whereCompareCy);

                        $dailyStmtCy = $pdo->prepare("
                            SELECT $dayExprCy as day_of_month, SUM(volume_liter) as daily_vol
                            FROM stock_raw
                            WHERE $whereCompareCySql
                            GROUP BY day_of_month
                        ");
                        $dailyStmtCy->execute($paramsCompareCy);
                        $cyDaily = $dailyStmtCy->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

                        $pyDaily = [];
                        if ($has2025) {
                            try {
                                $dailyStmtPy = $pdo->prepare("
                                    SELECT $dayExprCy as day_of_month, SUM(volume_liter) as daily_vol
                                    FROM db25.stock_raw
                                    WHERE $whereCompareCySql
                                    GROUP BY day_of_month
                                ");
                                $dailyStmtPy->execute($paramsCompareCy);
                                $pyDaily = $dailyStmtPy->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
                            } catch (\Throwable $e) {}
                        }

                        $daysInMonth = \Carbon\Carbon::create($endYear, $endMonth, 1)->daysInMonth;
                        $totCy = 0; $totPy = 0;
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $cVol = (float)($cyDaily[$d] ?? 0);
                            $pVol = (float)($pyDaily[$d] ?? 0);
                            $totCy += $cVol; $totPy += $pVol;
                            $delta = $cVol - $pVol;
                            $growth = $pVol > 0 ? (($cVol - $pVol) / $pVol) * 100 : ($cVol > 0 ? 100 : 0);

                            fputcsv($handle, [
                                sprintf('Tgl %02d', $d),
                                $d,
                                sprintf('%02d/%02d/%d', $d, $endMonth, $endYear),
                                round($cVol, 2),
                                round($pVol, 2),
                                round($delta, 2),
                                round($growth, 1) . '%'
                            ]);
                        }

                        $totGrowth = $totPy > 0 ? (($totCy - $totPy) / $totPy) * 100 : ($totCy > 0 ? 100 : 0);
                        fputcsv($handle, [
                            'Total Bulan', '-', sprintf('Bulan %02d (Total)', $endMonth),
                            round($totCy, 2),
                            round($totPy, 2),
                            round($totCy - $totPy, 2),
                            round($totGrowth, 1) . '%'
                        ]);
                    } elseif ($exportType === 'stock_raw') {
                        // 16 Columns matching Excel Submissions sheet
                        $headerRow = [
                            'Submission Date', 'Tanggal Pencatatan Stok', 'Region', 'Area', 'SAP', 'Nama Toko',
                            'Keterangan', 'Brand', 'Produk', 'Warna', 'Kemasan Galon', 'Kuantiti Galon',
                            'Kemasan Pail', 'Kuantiti Pail', 'Vol (Liter)', 'conf'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT submission_date, tgl_catat, region, area, sap, store_name,
                                   keterangan, brand, produk, warna, kemasan_galon, qty_galon,
                                   kemasan_pail, qty_pail, volume_liter, conf
                            FROM stock_raw
                            WHERE $whereSql
                            ORDER BY submission_date ASC, id ASC
                        ");
                        $stmt->execute($params);
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $row['submission_date'] ?? '',
                                $row['tgl_catat'] ?? '',
                                $row['region'] ?? '',
                                $row['area'] ?? '',
                                $row['sap'] ?? '',
                                $row['store_name'] ?? '',
                                $row['keterangan'] ?? '',
                                $row['brand'] ?? '',
                                $row['produk'] ?? '',
                                $row['warna'] ?? '',
                                $row['kemasan_galon'] ?? '',
                                $row['qty_galon'] ?? '',
                                $row['kemasan_pail'] ?? '',
                                $row['qty_pail'] ?? '',
                                $row['volume_liter'] ?? '',
                                $row['conf'] ?? ''
                            ]);
                        }
                    } elseif ($exportType === 'stock_summ') {
                        // Summ / SCM format
                        $offtakeSqlite = storage_path('app/dulux_data/offtake_2026.sqlite');
                        $hasOfftake = file_exists($offtakeSqlite);
                        if ($hasOfftake) {
                            try {
                                $pdo->exec("ATTACH DATABASE '{$offtakeSqlite}' AS offtake_db");
                            } catch (\Throwable $e) {
                                $hasOfftake = false;
                            }
                        }

                        $headerRow = [
                            'No', 'SAP', 'Nama Toko', 'Region', 'Area', 'Category Store',
                            'Stock End (L)', 'Offtake (L)', 'SCM (Stock Cover Month)'
                        ];
                        fputcsv($handle, $headerRow);

                        if ($hasOfftake) {
                            $summSql = "
                                SELECT s.sap, s.store_name, MIN(s.region) as region, MIN(s.area) as area,
                                       MIN(s.derp) as category_store,
                                       SUM(s.volume_liter) as stock_vol,
                                       COALESCE(o.offtake_vol, 0) as offtake_vol,
                                       CASE WHEN COALESCE(o.offtake_vol, 0) > 0 THEN ROUND(SUM(s.volume_liter) / o.offtake_vol, 2) ELSE 0 END as scm
                                FROM stock_raw s
                                LEFT JOIN (
                                    SELECT sap, SUM(volume_liter) as offtake_vol
                                    FROM offtake_db.offtake_raw
                                    WHERE month BETWEEN {$sMonth} AND {$eMonth}
                                    GROUP BY sap
                                ) o ON s.sap = o.sap
                                WHERE {$whereSql}
                                GROUP BY s.sap, s.store_name
                                ORDER BY CAST(s.sap AS INTEGER) ASC, s.sap ASC
                            ";
                        } else {
                            $summSql = "
                                SELECT sap, store_name, MIN(region) as region, MIN(area) as area,
                                       MIN(derp) as category_store,
                                       SUM(volume_liter) as stock_vol,
                                       0 as offtake_vol,
                                       0 as scm
                                FROM stock_raw
                                WHERE {$whereSql}
                                GROUP BY sap, store_name
                                ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                            ";
                        }

                        $stmt = $pdo->prepare($summSql);
                        $stmt->execute($params);
                        $no = 1;
                        $totStock = 0;
                        $totOfftake = 0;

                        while ($s = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $stk = (float)($s['stock_vol'] ?? 0);
                            $off = (float)($s['offtake_vol'] ?? 0);
                            $scm = (float)($s['scm'] ?? 0);
                            $totStock += $stk;
                            $totOfftake += $off;

                            fputcsv($handle, [
                                $no++,
                                $s['sap'],
                                $s['store_name'],
                                $s['region'],
                                $s['area'],
                                !empty($s['category_store']) ? $s['category_store'] : 'Retail',
                                round($stk, 2),
                                round($off, 2),
                                round($scm, 2)
                            ]);
                        }

                        $totScm = $totOfftake > 0 ? round($totStock / $totOfftake, 2) : 0;
                        fputcsv($handle, [
                            '', 'Grand Total', 'Seluruh Toko Terfilter', '', '', '',
                            round($totStock, 2),
                            round($totOfftake, 2),
                            round($totScm, 2)
                        ]);
                    } else {
                        // stock_pivot: Pivotable format
                        $headerRow = [
                            'No', 'SAP', 'Nama Toko', 'Region', 'Area',
                            'Dulux (L)', 'Catylac Smart Choice (L)', 'Catylac (L)', 'Grand Total (L)'
                        ];
                        fputcsv($handle, $headerRow);

                        $storeSql = "
                            SELECT sap, store_name, MIN(region) as region, MIN(area) as area,
                                   SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as vol_dulux,
                                   SUM(CASE WHEN brand = 'Catylac Smart Choice' THEN volume_liter ELSE 0 END) as vol_catylac_smart_choice,
                                   SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as vol_catylac,
                                   SUM(volume_liter) as total_vol
                            FROM stock_raw
                            WHERE $whereSql
                            GROUP BY sap, store_name
                            ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                        ";
                        $stmt = $pdo->prepare($storeSql);
                        $stmt->execute($params);

                        $no = 1;
                        $totDulux = 0;
                        $totSmart = 0;
                        $totCatylac = 0;
                        $totGrand = 0;

                        while ($s = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $d = (float)($s['vol_dulux'] ?? 0);
                            $sc = (float)($s['vol_catylac_smart_choice'] ?? 0);
                            $c = (float)($s['vol_catylac'] ?? 0);
                            $t = (float)($s['total_vol'] ?? 0);

                            $totDulux += $d;
                            $totSmart += $sc;
                            $totCatylac += $c;
                            $totGrand += $t;

                            fputcsv($handle, [
                                $no++,
                                $s['sap'],
                                $s['store_name'],
                                $s['region'],
                                $s['area'],
                                round($d, 2),
                                round($sc, 2),
                                round($c, 2),
                                round($t, 2)
                            ]);
                        }

                        fputcsv($handle, [
                            '', 'Grand Total', 'Seluruh Toko Terfilter', '', '',
                            round($totDulux, 2),
                            round($totSmart, 2),
                            round($totCatylac, 2),
                            round($totGrand, 2)
                        ]);
                    }

                    fclose($handle);
                };

                return response()->stream($callback, 200, $headers);
            }
        }

        // Custom Streamed CSV Export for Dulux Out of Stock (OOS) Report (Summary, Weekly Pivot, Raw Submissions)
        if (in_array($template->code, ['RPT-DULUX-OOS-SSO', 'RPT-DULUX-OOS-LSO']) || str_contains($template->code, 'OOS')) {
            $sqlitePath = storage_path('app/dulux_data/oos_2026.sqlite');
            $gzPath     = storage_path('app/dulux_data/oos_2026.sqlite.gz');
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
                        \Log::error("Auto-extraction of oos_2026.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            if (file_exists($sqlitePath)) {
                $exportType = $request->query('export_type', 'oos_summary');
                $filename = match($exportType) {
                    'oos_raw' => "raw-data-oos-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv",
                    'oos_weekly' => "rekap-mingguan-oos-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv",
                    default => "ringkasan-alasan-oos-{$startYear}_{$startMonth}-{$endYear}_{$endMonth}.csv"
                };

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
                    $selectedChannel = $request->query('channel', '');
                    $showNoOos = (bool)$request->query('show_no_oos', 0);

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    if ($sMonth > $eMonth) {
                        $tmp = $sMonth; $sMonth = $eMonth; $eMonth = $tmp;
                    }

                    $where = ["month BETWEEN ? AND ?"];
                    $params = [$sMonth, $eMonth];

                    if ($selectedChannel && in_array(strtoupper($selectedChannel), ['LSO', 'SSO'])) {
                        $where[] = "channel = ?";
                        $params[] = strtoupper($selectedChannel);
                    }
                    if ($selectedRegion) {
                        $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                        $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                        $where[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                        foreach ($rsmVariants as $rv) {
                            $params[] = $rv;
                        }
                        $params[] = $selectedRegion;
                    }
                    if ($selectedAreaName) {
                        $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                        $params[] = $selectedAreaName;
                    }
                    if ($selectedStoreName) {
                        $where[] = "store_name = ?";
                        $params[] = $selectedStoreName;
                    }
                    if ($search) {
                        $where[] = "(store_name LIKE ? OR sap LIKE ? OR produk LIKE ? OR base_color LIKE ? OR alasan_oos LIKE ?)";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                        $params[] = "%{$search}%";
                    }
                    $whereSql = implode(' AND ', $where);

                    if ($exportType === 'oos_raw') {
                        $rawWhere = $where;
                        if (!$showNoOos) {
                            $rawWhere[] = "(is_oos = 1 AND UPPER(TRIM(COALESCE(produk, ''))) != 'NO OOS')";
                        }
                        $rawWhereSql = implode(' AND ', $rawWhere);

                        $headerRow = [
                            'Channel', 'Submission Code', 'Submission Date', 'Tanggal OOS', 'Week',
                            'Region', 'Area', 'RSM Area', 'Account', 'SAP', 'DERP/Category', 'Nama Toko',
                            'Produk', 'Base/Warna', 'Kemasan/Size', 'Lama OOS (Hari)', 'Saran Qty Order', 'Alasan OOS', 'Is OOS'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT channel, submission_code, submission_date, tanggal_oos, week,
                                   region, area, rsm_area, account, sap, derp, store_name,
                                   produk, base_color, kemasan_size, lama_oos_hari, saran_qty_order, alasan_oos, is_oos
                            FROM oos_raw
                            WHERE $rawWhereSql
                            ORDER BY tanggal_oos ASC, id ASC
                        ");
                        $stmt->execute($params);
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $row['channel'] ?? '',
                                $row['submission_code'] ?? '',
                                $row['submission_date'] ?? '',
                                $row['tanggal_oos'] ?? '',
                                $row['week'] ?? '',
                                $row['region'] ?? '',
                                $row['area'] ?? '',
                                $row['rsm_area'] ?? '',
                                $row['account'] ?? '',
                                $row['sap'] ?? '',
                                $row['derp'] ?? '',
                                $row['store_name'] ?? '',
                                $row['produk'] ?? '',
                                $row['base_color'] ?? '',
                                $row['kemasan_size'] ?? '',
                                $row['lama_oos_hari'] ?? '',
                                $row['saran_qty_order'] ?? '',
                                $row['alasan_oos'] ?? '',
                                ($row['is_oos'] == 1 ? 'OOS' : 'No OOS')
                            ]);
                        }
                    } elseif ($exportType === 'oos_weekly') {
                        // Week list
                        $weekStmt = $pdo->prepare("SELECT DISTINCT week FROM oos_raw WHERE $whereSql AND week IS NOT NULL ORDER BY week ASC");
                        $weekStmt->execute($params);
                        $weeks = $weekStmt->fetchAll(\PDO::FETCH_COLUMN);

                        $headerRow = array_merge([
                            'No', 'Channel', 'Region', 'Area', 'SAP', 'Nama Toko',
                            'Produk', 'Base / Warna', 'Kemasan', 'Alasan OOS'
                        ], array_map(fn($w) => "Week " . $w, $weeks), ['Grand Total']);
                        fputcsv($handle, $headerRow);

                        $weeklyWhere = array_merge($where, ["is_oos = 1"]);
                        $weeklyWhereSql = implode(' AND ', $weeklyWhere);

                        $stmt = $pdo->prepare("
                            SELECT sap, store_name, MIN(region) as region, MIN(area) as area, MIN(channel) as channel,
                                   produk, base_color, kemasan_size, alasan_oos,
                                   COUNT(*) as grand_total
                            FROM oos_raw
                            WHERE $weeklyWhereSql
                            GROUP BY sap, store_name, produk, base_color, kemasan_size, alasan_oos
                            ORDER BY region ASC, area ASC, store_name ASC, produk ASC
                        ");
                        $stmt->execute($params);

                        $no = 1;
                        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $subWhere = array_merge($weeklyWhere, [
                                "store_name = ?",
                                "COALESCE(produk, '') = ?",
                                "COALESCE(base_color, '') = ?",
                                "COALESCE(kemasan_size, '') = ?",
                                "COALESCE(alasan_oos, '') = ?"
                            ]);
                            $subStmt = $pdo->prepare("
                                SELECT week, COUNT(*) as cnt
                                FROM oos_raw
                                WHERE " . implode(' AND ', $subWhere) . "
                                GROUP BY week
                            ");
                            $subStmt->execute(array_merge($params, [
                                $r['store_name'],
                                $r['produk'] ?? '',
                                $r['base_color'] ?? '',
                                $r['kemasan_size'] ?? '',
                                $r['alasan_oos'] ?? ''
                            ]));
                            $wCounts = $subStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

                            $rowLine = [
                                $no++,
                                $r['channel'] ?? '',
                                $r['region'] ?? '',
                                $r['area'] ?? '',
                                $r['sap'] ?? '',
                                $r['store_name'] ?? '',
                                $r['produk'] ?? '',
                                $r['base_color'] ?? '',
                                $r['kemasan_size'] ?? '',
                                $r['alasan_oos'] ?? ''
                            ];
                            foreach ($weeks as $wk) {
                                $rowLine[] = (int)($wCounts[$wk] ?? 0);
                            }
                            $rowLine[] = (int)$r['grand_total'];
                            fputcsv($handle, $rowLine);
                        }
                    } else {
                        // oos_summary
                        $headerRow = ['No', 'Alasan OOS', 'Jumlah Toko Terdampak', 'Total Kejadian OOS', 'Persentase (%)'];
                        fputcsv($handle, $headerRow);

                        $totIncStmt = $pdo->prepare("SELECT COUNT(*) FROM oos_raw WHERE $whereSql AND is_oos = 1");
                        $totIncStmt->execute($params);
                        $totInc = (int)$totIncStmt->fetchColumn();

                        $stmt = $pdo->prepare("
                            SELECT 
                                COALESCE(NULLIF(TRIM(alasan_oos), ''), 'Lain-lain') as reason,
                                COUNT(DISTINCT store_name) as store_count,
                                COUNT(*) as incident_count
                            FROM oos_raw
                            WHERE $whereSql AND is_oos = 1
                            GROUP BY reason
                            ORDER BY incident_count DESC
                        ");
                        $stmt->execute($params);

                        $no = 1;
                        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $pct = $totInc > 0 ? round(($r['incident_count'] / $totInc) * 100, 1) : 0;
                            fputcsv($handle, [
                                $no++,
                                $r['reason'],
                                $r['store_count'],
                                $r['incident_count'],
                                $pct . '%'
                            ]);
                        }
                    }

                    fclose($handle);
                };

                return response()->stream($callback, 200, $headers);
            }
        }

        // --- Daily Maintenance Export Handling ---
        if ($template->code === 'RPT-DULUX-DAILY-MAINTENANCE' || str_contains($template->code, 'DAILY-MAINTENANCE')) {
            $sqlitePath = storage_path('app/dulux_data/daily_maintenance.sqlite');
            $gzPath     = storage_path('app/dulux_data/daily_maintenance.sqlite.gz');
            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                        \Log::error("Auto-extraction of daily_maintenance.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            if (file_exists($sqlitePath)) {
                $exportType = $request->query('export_type', 'dm_raw');
                $filename = 'Export_Dulux_Daily_Maintenance_' . $exportType . '_' . date('Ymd_His') . '.csv';

                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0'
                ];

                $callback = function() use ($sqlitePath, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $request, $exportType) {
                    $handle = fopen('php://output', 'w');
                    fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                    $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;
                    $search = $request->query('q');
                    $selectedMachineType = $request->query('machine_type', '');
                    $selectedCategory = $request->query('category', '');

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    $sYear  = (int)($startYear ?: 2026);
                    $eYear  = (int)($endYear ?: 2026);

                    $where = [];
                    $params = [];

                    if ($sYear === $eYear) {
                        $where[] = "year = ?";
                        $params[] = $sYear;
                        $where[] = "month >= ? AND month <= ?";
                        $params[] = $sMonth;
                        $params[] = $eMonth;
                    } else {
                        $where[] = "((year = ? AND month >= ?) OR (year = ? AND month <= ?) OR (year > ? AND year < ?))";
                        $params[] = $sYear; $params[] = $sMonth;
                        $params[] = $eYear; $params[] = $eMonth;
                        $params[] = $sYear; $params[] = $eYear;
                    }

                    if ($selectedRegion) {
                        $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                        $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                        $where[] = "rsm_area IN ($inPlaceholders)";
                        foreach ($rsmVariants as $rv) {
                            $params[] = $rv;
                        }
                    }
                    if ($selectedAreaName) {
                        $where[] = "area = ?";
                        $params[] = $selectedAreaName;
                    }
                    if ($selectedStoreName) {
                        $where[] = "store_name = ?";
                        $params[] = $selectedStoreName;
                    }
                    if ($selectedMachineType) {
                        $where[] = "machine_type = ?";
                        $params[] = $selectedMachineType;
                    }
                    if ($selectedCategory) {
                        $where[] = "category = ?";
                        $params[] = $selectedCategory;
                    }
                    if ($search) {
                        $where[] = "(store_name LIKE ? OR sap_code LIKE ? OR machine_no LIKE ? OR dc_name LIKE ? OR tl_name LIKE ?)";
                        $like = "%{$search}%";
                        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
                    }

                    $whereSql = implode(' AND ', $where);

                    if ($exportType === 'dm_stores') {
                        $headerRow = [
                            'No', 'Nama Toko', 'Kode SAP', 'Kategori Toko', 'Region (RSM Area)', 'Area',
                            'Tipe Mesin POST', 'No Mesin POST (Serial)', 'Total Frekuensi Perawatan',
                            'Tanggal Terakhir Perawatan', 'Tinta OK (Kali)', 'Pembersihan OK (Kali)', 'Tingkat Kepatuhan (%)'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT 
                                store_name, sap_code, category, rsm_area, area,
                                machine_type, machine_no,
                                COUNT(*) as total_checks,
                                MAX(tanggal_report) as last_date,
                                SUM(tinta_ok) as tinta_ok_cnt,
                                SUM(pembersihan_all_ok) as clean_ok_cnt,
                                ROUND(AVG(tinta_ok) * 100, 1) as compliance_pct
                            FROM dm_raw
                            WHERE $whereSql
                            GROUP BY store_name, machine_no
                            ORDER BY total_checks DESC, store_name ASC
                        ");
                        $stmt->execute($params);
                        $no = 1;
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $no++,
                                $row['store_name'] ?? '',
                                $row['sap_code'] ?? '',
                                $row['category'] ?? '',
                                $row['rsm_area'] ?? '',
                                $row['area'] ?? '',
                                $row['machine_type'] ?? '',
                                $row['machine_no'] ?? '',
                                (int)$row['total_checks'],
                                $row['last_date'] ?? '',
                                (int)$row['tinta_ok_cnt'],
                                (int)$row['clean_ok_cnt'],
                                ($row['compliance_pct'] ?? 0) . '%'
                            ]);
                        }
                    } else {
                        // dm_raw
                        $headerRow = [
                            'No', 'Tahun', 'Bulan', 'Submission Date', 'Tanggal Report', 'Nama Toko', 'Kode SAP',
                            'Kategori Toko', 'Region (RSM Area)', 'Area', 'Nama TL', 'Tipe Mesin POST',
                            'No Mesin POST', 'Nama DC / Petugas', 'Tinta OK', 'Nozzle/Brush OK',
                            'Mix2Win Steps OK (/12)', 'Pembersihan Lengkap OK', 'Kesimpulan'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT 
                                year, month, submission_date, tanggal_report, store_name, sap_code, category, rsm_area, area,
                                tl_name, machine_type, machine_no, dc_name, kesimpulan,
                                tinta_ok, (CASE WHEN d200_nozzle_ok = 1 OR discovery_brush_ok = 1 OR manual_nozzle_ok = 1 THEN 1 ELSE 0 END) as nozzle_ok,
                                mix2win_steps_ok, pembersihan_all_ok
                            FROM dm_raw
                            WHERE $whereSql
                            ORDER BY tanggal_report DESC, id DESC
                        ");
                        $stmt->execute($params);
                        $no = 1;
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $no++,
                                $row['year'],
                                $row['month'],
                                $row['submission_date'] ?? '',
                                $row['tanggal_report'] ?? '',
                                $row['store_name'] ?? '',
                                $row['sap_code'] ?? '',
                                $row['category'] ?? '',
                                $row['rsm_area'] ?? '',
                                $row['area'] ?? '',
                                $row['tl_name'] ?? '',
                                $row['machine_type'] ?? '',
                                $row['machine_no'] ?? '',
                                $row['dc_name'] ?? '',
                                ($row['tinta_ok'] == 1 ? 'OK' : 'NO'),
                                ($row['nozzle_ok'] == 1 ? 'OK' : '-'),
                                $row['mix2win_steps_ok'] . '/12',
                                ($row['pembersihan_all_ok'] == 1 ? 'OK' : 'NO'),
                                $row['kesimpulan'] ?? ''
                            ]);
                        }
                    }

                    fclose($handle);
                };

                return response()->stream($callback, 200, $headers);
            }
        }

        // --- Customer Database Export Handling ---
        if ($template->code === 'RPT-DULUX-DATABASE-PELANGGAN' || str_contains($template->code, 'PELANGGAN')) {
            $sqlitePath = storage_path('app/dulux_data/customer_db.sqlite');
            $gzPath     = storage_path('app/dulux_data/customer_db.sqlite.gz');
            if (!file_exists($sqlitePath) || filesize($sqlitePath) < 500000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                        \Log::error("Auto-extraction of customer_db.sqlite.gz failed: " . $e->getMessage());
                    }
                }
            }

            if (file_exists($sqlitePath)) {
                $exportType = $request->query('export_type', 'cust_raw');
                $filename = 'Export_Dulux_Customer_Database_' . $exportType . '_' . date('Ymd_His') . '.csv';

                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0'
                ];

                $callback = function() use ($sqlitePath, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $request, $exportType) {
                    $handle = fopen('php://output', 'w');
                    fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

                    $pdo = new \PDO("sqlite:" . $sqlitePath);
                    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                    $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;
                    $search = $request->query('q');
                    $selectedCustomerType = $request->query('cust_type', '');
                    $selectedBrand        = $request->query('brand', '');
                    $selectedReason       = $request->query('reason', '');

                    $sMonth = max(1, min(12, (int)$startMonth));
                    $eMonth = max(1, min(12, (int)$endMonth));
                    $sYear  = (int)($startYear ?: 2025);
                    $eYear  = (int)($endYear ?: 2026);

                    $where = [];
                    $params = [];

                    if ($sYear === $eYear) {
                        $where[] = "year = ?";
                        $params[] = $sYear;
                        $where[] = "month >= ? AND month <= ?";
                        $params[] = $sMonth;
                        $params[] = $eMonth;
                    } else {
                        $where[] = "((year = ? AND month >= ?) OR (year = ? AND month <= ?) OR (year > ? AND year < ?))";
                        $params[] = $sYear; $params[] = $sMonth;
                        $params[] = $eYear; $params[] = $eMonth;
                        $params[] = $sYear; $params[] = $eYear;
                    }

                    if ($selectedRegion) {
                        $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                        $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                        $where[] = "rsm_area IN ($inPlaceholders)";
                        foreach ($rsmVariants as $rv) {
                            $params[] = $rv;
                        }
                    }
                    if ($selectedAreaName) {
                        $where[] = "area = ?";
                        $params[] = $selectedAreaName;
                    }
                    if ($selectedStoreName) {
                        $where[] = "store_name = ?";
                        $params[] = $selectedStoreName;
                    }
                    if ($selectedCustomerType) {
                        $where[] = "tipe_pelanggan = ?";
                        $params[] = $selectedCustomerType;
                    }
                    if ($selectedBrand) {
                        $where[] = "(brand_dicari LIKE ? OR brand_dibeli LIKE ?)";
                        $params[] = "%{$selectedBrand}%";
                        $params[] = "%{$selectedBrand}%";
                    }
                    if ($selectedReason) {
                        $where[] = "alasan = ?";
                        $params[] = $selectedReason;
                    }
                    if ($search) {
                        $where[] = "(nama_pelanggan LIKE ? OR no_hp LIKE ? OR store_name LIKE ? OR sap_code LIKE ? OR alamat LIKE ? OR nama_dc LIKE ?)";
                        $like = "%{$search}%";
                        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
                    }

                    $whereSql = !empty($where) ? implode(' AND ', $where) : "1=1";

                    if ($exportType === 'cust_stores') {
                        $headerRow = [
                            'No', 'Nama Toko', 'Kode SAP', 'Region (RSM Area)', 'Area',
                            'Total Konsumen Terdata', 'Total Nilai Belanja (Rp)', 'Rata-Rata per Konsumen (Rp)',
                            'Total Beralih ke Dulux (Switching)', 'Jumlah DC / Promotor'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT 
                                store_name, sap_code, rsm_area, area,
                                COUNT(*) as total_customers,
                                COALESCE(SUM(value_pembelian), 0) as total_val,
                                COALESCE(AVG(value_pembelian), 0) as avg_val,
                                COALESCE(SUM(is_switched), 0) as switched_cnt,
                                COUNT(DISTINCT nama_dc) as total_dcs
                            FROM cust_raw
                            WHERE $whereSql
                            GROUP BY store_name
                            ORDER BY total_val DESC, total_customers DESC
                        ");
                        $stmt->execute($params);
                        $no = 1;
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $no++,
                                $row['store_name'] ?? '',
                                $row['sap_code'] ?? '',
                                $row['rsm_area'] ?? '',
                                $row['area'] ?? '',
                                (int)$row['total_customers'],
                                (float)$row['total_val'],
                                (float)$row['avg_val'],
                                (int)$row['switched_cnt'],
                                (int)$row['total_dcs']
                            ]);
                        }
                    } else {
                        // cust_raw
                        $headerRow = [
                            'No', 'Tahun', 'Bulan', 'Submission Date', 'Tanggal Transaksi', 'Nama Toko', 'Kode SAP', 'SAP Gab',
                            'Region (RSM Area)', 'Area', 'Nama Konsumen', 'Alamat', 'No HP / WhatsApp',
                            'Tipe Pelanggan', 'Program Mitra Dulux', 'Tujuan ke Toko', 'Brand Dicari', 'Brand Dibeli',
                            'Alasan Pilih Brand', 'Tipe Pengecatan', 'Perlu Preview Warna', 'Nilai Pembelian (Rp)',
                            'Status Switch ke Dulux', 'Nama DC / Promotor', 'Catatan / Keterangan', 'Foto 1', 'Foto 2', 'Foto 3'
                        ];
                        fputcsv($handle, $headerRow);

                        $stmt = $pdo->prepare("
                            SELECT 
                                year, month, submission_date, tanggal, store_name, sap_code, sap_gab,
                                rsm_area, area, nama_pelanggan, alamat, no_hp,
                                tipe_pelanggan, painter_info, tujuan_ke_toko, brand_dicari, brand_dibeli,
                                alasan, tipe_pengecatan, memerlukan_preview, value_pembelian,
                                is_switched, nama_dc, keterangan, foto_1, foto_2, foto_3
                            FROM cust_raw
                            WHERE $whereSql
                            ORDER BY year DESC, id DESC
                        ");
                        $stmt->execute($params);
                        $no = 1;
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            fputcsv($handle, [
                                $no++,
                                $row['year'],
                                $row['month'],
                                $row['submission_date'] ?? '',
                                $row['tanggal'] ?? '',
                                $row['store_name'] ?? '',
                                $row['sap_code'] ?? '',
                                $row['sap_gab'] ?? '',
                                $row['rsm_area'] ?? '',
                                $row['area'] ?? '',
                                $row['nama_pelanggan'] ?? '',
                                $row['alamat'] ?? '',
                                $row['no_hp'] ?? '',
                                $row['tipe_pelanggan'] ?? '',
                                $row['painter_info'] ?? '',
                                $row['tujuan_ke_toko'] ?? '',
                                $row['brand_dicari'] ?? '',
                                $row['brand_dibeli'] ?? '',
                                $row['alasan'] ?? '',
                                $row['tipe_pengecatan'] ?? '',
                                $row['memerlukan_preview'] ?? '',
                                (float)$row['value_pembelian'],
                                ($row['is_switched'] == 1 ? 'Ya (Switch ke Dulux)' : 'Tidak'),
                                $row['nama_dc'] ?? '',
                                $row['keterangan'] ?? '',
                                $row['foto_1'] ?? '',
                                $row['foto_2'] ?? '',
                                $row['foto_3'] ?? ''
                            ]);
                        }
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
                  ->orWhere('employee_no', 'like', "%{$search}%");
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
                  ->orWhere('employee_no', 'like', "%{$search}%")
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                    $eq->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
                $q->where('full_name', 'like', "%{$search}%")->orWhere('employee_no', 'like', "%{$search}%");
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
     * Calculate Dulux Stock End Data (Pivotable Store Volume, SCM / Summ & Raw Data Submissions)
     */
    protected function calculateStockDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedBrand = 'ALL', $search = null, $stockPage = 1, $summPage = 1, $rawPage = 1, $perPage = 50)
    {
        $selectedYear = (int)$endYear;
        $sqlitePath   = storage_path("app/dulux_data/stock_{$selectedYear}.sqlite");
        $gzPath       = storage_path("app/dulux_data/stock_{$selectedYear}.sqlite.gz");

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
                    \Log::error("Auto-extraction of stock_{$selectedYear}.sqlite.gz failed: " . $e->getMessage());
                }
            } elseif (!file_exists($sqlitePath)) {
                $sqlitePath = storage_path('app/dulux_data/stock_2026.sqlite');
                $gzPath     = storage_path('app/dulux_data/stock_2026.sqlite.gz');
            }
        }

        if (!file_exists($sqlitePath)) {
            return [
                'months' => [],
                'pivotable' => ['rows' => [], 'grand_total_dulux' => 0, 'grand_total_catylac_sc' => 0, 'grand_total_catylac' => 0, 'grand_total_all' => 0, 'total_stores' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                'summ' => ['rows' => [], 'total_stock' => 0, 'total_offtake' => 0, 'avg_scm' => 0, 'total_stores' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
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
            $activeMonths[$m] = $monthNames[$m] . ' ' . $selectedYear;
        }

        $cacheKey = 'stock_dash_v3_' . md5($template->id . '_' . $sMonth . '_' . $eMonth . '_' . $selectedYear . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $selectedBrand . '_' . $search . '_' . $stockPage . '_' . $summPage . '_' . $rawPage);

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sMonth, $eMonth, $activeMonths, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedBrand, $search, $stockPage, $summPage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = ["month BETWEEN ? AND ?"];
                $params = [$sMonth, $eMonth];

                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $where[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                    foreach ($rsmVariants as $rv) {
                        $params[] = $rv;
                    }
                    $params[] = $selectedRegion;
                }
                if ($selectedAreaId) {
                    $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $params[] = $selectedAreaId;
                }
                if ($selectedLocationId) {
                    $where[] = "store_name = ?";
                    $params[] = $selectedLocationId;
                }
                if ($selectedBrand === 'DULUX') {
                    $where[] = "brand = 'Dulux'";
                } elseif ($selectedBrand === 'CATYLAC') {
                    $where[] = "(brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%')";
                }
                if ($search) {
                    $where[] = "(store_name LIKE ? OR sap LIKE ? OR brand LIKE ? OR produk LIKE ?)";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                }
                $whereSql = implode(' AND ', $where);

                // 1. Pivotable: Grouped by SAP, Store Name with breakdown Dulux, Catylac Smart Choice, Catylac, Grand Total
                $pivotGrandSql = "
                    SELECT SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as grand_total_dulux,
                           SUM(CASE WHEN brand = 'Catylac Smart Choice' THEN volume_liter ELSE 0 END) as grand_total_catylac_sc,
                           SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as grand_total_catylac,
                           SUM(volume_liter) as grand_total_all
                    FROM stock_raw
                    WHERE $whereSql
                ";
                $pivotGrandStmt = $pdo->prepare($pivotGrandSql);
                $pivotGrandStmt->execute($params);
                $pivotGrand = $pivotGrandStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

                $countPivotSql = "SELECT COUNT(DISTINCT sap || '---' || store_name) FROM stock_raw WHERE $whereSql";
                $countPivotStmt = $pdo->prepare($countPivotSql);
                $countPivotStmt->execute($params);
                $totalPivotStores = (int)$countPivotStmt->fetchColumn();

                $pivotOffset = ($stockPage - 1) * $perPage;
                $pivotStoreSql = "
                    SELECT sap, store_name, MIN(region) as region, MIN(area) as area,
                           SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_vol,
                           SUM(CASE WHEN brand = 'Catylac Smart Choice' THEN volume_liter ELSE 0 END) as catylac_sc_vol,
                           SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as catylac_vol,
                           SUM(volume_liter) as total_vol
                    FROM stock_raw
                    WHERE $whereSql
                    GROUP BY sap, store_name
                    ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                    LIMIT $perPage OFFSET $pivotOffset
                ";
                $pivotStoreStmt = $pdo->prepare($pivotStoreSql);
                $pivotStoreStmt->execute($params);
                $pivotStores = $pivotStoreStmt->fetchAll(\PDO::FETCH_ASSOC);

                // 2. Summ: SCM calculation (Stock Cover Month = Stock / Offtake)
                $offtakeSqlite = storage_path('app/dulux_data/offtake_2026.sqlite');
                $hasOfftake = file_exists($offtakeSqlite);
                if ($hasOfftake) {
                    try {
                        $pdo->exec("ATTACH DATABASE '{$offtakeSqlite}' AS offtake_db");
                    } catch (\Throwable $e) {
                        $hasOfftake = false;
                    }
                }

                $summOffset = ($summPage - 1) * $perPage;
                if ($hasOfftake) {
                    $summSql = "
                        SELECT s.sap, s.store_name, MIN(s.region) as region, MIN(s.area) as area,
                               MIN(s.derp) as category_store,
                               SUM(CASE WHEN s.brand = 'Dulux' THEN s.volume_liter ELSE 0 END) as dulux_stock,
                               SUM(CASE WHEN (s.brand LIKE '%Catylac%' OR s.brand LIKE '%Smart Choice%') THEN s.volume_liter ELSE 0 END) as catylac_stock,
                               SUM(s.volume_liter) as total_stock,
                               COALESCE(o.dulux_offtake, 0) as dulux_offtake,
                               COALESCE(o.catylac_offtake, 0) as catylac_offtake,
                               COALESCE(o.total_offtake, 0) as total_offtake
                        FROM stock_raw s
                        LEFT JOIN (
                            SELECT sap,
                                   SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_offtake,
                                   SUM(CASE WHEN (brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%') THEN volume_liter ELSE 0 END) as catylac_offtake,
                                   SUM(volume_liter) as total_offtake
                            FROM offtake_db.offtake_raw
                            WHERE month BETWEEN {$sMonth} AND {$eMonth}
                            GROUP BY sap
                        ) o ON s.sap = o.sap
                        WHERE {$whereSql}
                        GROUP BY s.sap, s.store_name
                        ORDER BY CAST(s.sap AS INTEGER) ASC, s.sap ASC
                        LIMIT $perPage OFFSET $summOffset
                    ";

                    $summGrandSql = "
                        SELECT SUM(s.volume_liter) as total_stock,
                               COALESCE(SUM(o.total_offtake), 0) as total_offtake
                        FROM stock_raw s
                        LEFT JOIN (
                            SELECT sap, SUM(volume_liter) as total_offtake
                            FROM offtake_db.offtake_raw
                            WHERE month BETWEEN {$sMonth} AND {$eMonth}
                            GROUP BY sap
                        ) o ON s.sap = o.sap
                        WHERE {$whereSql}
                    ";
                } else {
                    $summSql = "
                        SELECT sap, store_name, MIN(region) as region, MIN(area) as area,
                               MIN(derp) as category_store,
                               SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_stock,
                               SUM(CASE WHEN (brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%') THEN volume_liter ELSE 0 END) as catylac_stock,
                               SUM(volume_liter) as total_stock,
                               0 as dulux_offtake,
                               0 as catylac_offtake,
                               0 as total_offtake
                        FROM stock_raw
                        WHERE {$whereSql}
                        GROUP BY sap, store_name
                        ORDER BY CAST(sap AS INTEGER) ASC, sap ASC
                        LIMIT $perPage OFFSET $summOffset
                    ";

                    $summGrandSql = "
                        SELECT SUM(volume_liter) as total_stock, 0 as total_offtake
                        FROM stock_raw
                        WHERE {$whereSql}
                    ";
                }

                $summStmt = $pdo->prepare($summSql);
                $summStmt->execute($params);
                $summStores = $summStmt->fetchAll(\PDO::FETCH_ASSOC);

                $summGrandStmt = $pdo->prepare($summGrandSql);
                $summGrandStmt->execute($params);
                $summGrand = $summGrandStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                $totStk = (float)($summGrand['total_stock'] ?? 0);
                $totOff = (float)($summGrand['total_offtake'] ?? 0);
                $avgScm = $totOff > 0 ? round($totStk / $totOff, 2) : 0;

                // 3. Raw Data Submissions (16 Columns matching Excel)
                $rawOffset = ($rawPage - 1) * $perPage;
                $rawCountSql = "SELECT COUNT(*) FROM stock_raw WHERE $whereSql";
                $rawCountStmt = $pdo->prepare($rawCountSql);
                $rawCountStmt->execute($params);
                $totalRaw = (int)$rawCountStmt->fetchColumn();

                $rawSql = "
                    SELECT submission_date, tgl_catat, region, area, sap, store_name,
                           keterangan, brand, produk, warna, kemasan_galon, qty_galon,
                           kemasan_pail, qty_pail, volume_liter, conf
                    FROM stock_raw
                    WHERE $whereSql
                    ORDER BY submission_date DESC, id DESC
                    LIMIT $perPage OFFSET $rawOffset
                ";
                $rawStmt = $pdo->prepare($rawSql);
                $rawStmt->execute($params);
                $rawRows = $rawStmt->fetchAll(\PDO::FETCH_ASSOC);

                return [
                    'months' => $activeMonths,
                    'pivotable' => [
                        'rows' => $pivotStores,
                        'grand_total_dulux' => (float)($pivotGrand['grand_total_dulux'] ?? 0),
                        'grand_total_catylac_sc' => (float)($pivotGrand['grand_total_catylac_sc'] ?? 0),
                        'grand_total_catylac' => (float)($pivotGrand['grand_total_catylac'] ?? 0),
                        'grand_total_all' => (float)($pivotGrand['grand_total_all'] ?? 0),
                        'total_stores' => $totalPivotStores,
                        'total' => $totalPivotStores,
                        'page' => $stockPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalPivotStores / $perPage),
                        'from' => $totalPivotStores > 0 ? ($pivotOffset + 1) : 0,
                        'to' => min($pivotOffset + $perPage, $totalPivotStores),
                    ],
                    'summ' => [
                        'rows' => $summStores,
                        'total_stock' => $totStk,
                        'total_offtake' => $totOff,
                        'avg_scm' => $avgScm,
                        'total_stores' => $totalPivotStores,
                        'total' => $totalPivotStores,
                        'page' => $summPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalPivotStores / $perPage),
                        'from' => $totalPivotStores > 0 ? ($summOffset + 1) : 0,
                        'to' => min($summOffset + $perPage, $totalPivotStores),
                    ],
                    'submissions' => [
                        'rows' => $rawRows,
                        'total' => $totalRaw,
                        'page' => $rawPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalRaw / $perPage),
                        'from' => $totalRaw > 0 ? ($rawOffset + 1) : 0,
                        'to' => min($rawOffset + $perPage, $totalRaw),
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Stock Dashboard: " . $e->getMessage());
                return [
                    'months' => $activeMonths,
                    'pivotable' => ['rows' => [], 'grand_total_dulux' => 0, 'grand_total_catylac_sc' => 0, 'grand_total_catylac' => 0, 'grand_total_all' => 0, 'total_stores' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'summ' => ['rows' => [], 'total_stock' => 0, 'total_offtake' => 0, 'avg_scm' => 0, 'total_stores' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
                ];
            }
        });
    }

    /**
     * Calculate Dulux Stock End Monthly Comparison (Current Year Month vs Previous Year Month)
     * e.g. Juli 2026 vs Juli 2025 with Daily Trend Line Chart Series (Day 1..31)
     */
    protected function calculateStockMonthlyCompareData($template, $targetMonth, $currentYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedBrand = 'ALL', $search = null)
    {
        $p26 = storage_path('app/dulux_data/stock_2026.sqlite');
        $p25 = storage_path('app/dulux_data/stock_2025.sqlite');
        $gz25 = storage_path('app/dulux_data/stock_2025.sqlite.gz');
        $gz26 = storage_path('app/dulux_data/stock_2026.sqlite.gz');

        // Auto-extract 2026 if needed
        if (!file_exists($p26) || filesize($p26) < 1000000) {
            if (file_exists($gz26)) {
                try {
                    $zp = gzopen($gz26, 'rb');
                    $tmpPath = $p26 . '.tmp.' . uniqid();
                    $fp = fopen($tmpPath, 'wb');
                    if ($zp && $fp) {
                        while (!gzeof($zp)) {
                            fwrite($fp, gzread($zp, 524288));
                        }
                        gzclose($zp);
                        fclose($fp);
                        @rename($tmpPath, $p26);
                        @chmod($p26, 0666);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Auto-extraction of stock_2026.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        // Auto-extract 2025 if needed
        if (!file_exists($p25) || filesize($p25) < 1000000) {
            if (file_exists($gz25)) {
                try {
                    $zp = gzopen($gz25, 'rb');
                    $tmpPath = $p25 . '.tmp.' . uniqid();
                    $fp = fopen($tmpPath, 'wb');
                    if ($zp && $fp) {
                        while (!gzeof($zp)) {
                            fwrite($fp, gzread($zp, 524288));
                        }
                        gzclose($zp);
                        fclose($fp);
                        @rename($tmpPath, $p25);
                        @chmod($p25, 0666);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Auto-extraction of stock_2025.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        $targetMonth = max(1, min(12, (int)$targetMonth));
        $previousYear = (int)$currentYear - 1;

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli',
            8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = $monthNames[$targetMonth] ?? 'Bulan ' . $targetMonth;

        $emptyResult = [
            'month' => $targetMonth,
            'month_name' => $monthName,
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'selected_brand' => $selectedBrand,
            'kpi' => [
                'cy_volume' => 0,
                'py_volume' => 0,
                'growth' => 0,
                'growth_diff' => 0,
                'cy_stores' => 0,
                'py_stores' => 0,
                'cy_avg_per_store' => 0,
                'py_avg_per_store' => 0,
                'dulux_vol' => 0,
                'dulux_pct' => 0,
                'catylac_vol' => 0,
                'catylac_pct' => 0,
            ],
            'daily_trend' => [
                'categories' => [],
                'days' => [],
                'cy_series' => [],
                'py_series' => [],
                'table' => [],
            ],
            'top_stores' => [],
        ];

        if (!file_exists($p26)) {
            return $emptyResult;
        }

        $cacheKey = 'stock_monthly_comp_v2_' . md5($template->id . '_' . $targetMonth . '_' . $currentYear . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $selectedBrand . '_' . $search);

        return Cache::remember($cacheKey, 600, function() use ($p26, $p25, $targetMonth, $currentYear, $previousYear, $monthName, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedBrand, $search, $emptyResult) {
            try {
                $pdo = new \PDO("sqlite:" . $p26);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $has2025 = file_exists($p25);
                if ($has2025) {
                    try {
                        $pdo->exec("ATTACH DATABASE '{$p25}' AS db25");
                    } catch (\Throwable $e) {
                        $has2025 = false;
                    }
                }

                // Build WHERE clause
                $whereCy = ["month = ?"];
                $paramsCy = [$targetMonth];
                $wherePy = ["month = ?"];
                $paramsPy = [$targetMonth];

                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $whereCy[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                    $wherePy[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                    foreach ($rsmVariants as $rv) {
                        $paramsCy[] = $rv;
                        $paramsPy[] = $rv;
                    }
                    $paramsCy[] = $selectedRegion;
                    $paramsPy[] = $selectedRegion;
                }
                if ($selectedAreaId) {
                    $whereCy[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $paramsCy[] = $selectedAreaId;
                    $wherePy[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $paramsPy[] = $selectedAreaId;
                }
                if ($selectedLocationId) {
                    $whereCy[] = "store_name = ?";
                    $paramsCy[] = $selectedLocationId;
                    $wherePy[] = "store_name = ?";
                    $paramsPy[] = $selectedLocationId;
                }
                if ($selectedBrand === 'DULUX') {
                    $whereCy[] = "brand = 'Dulux'";
                    $wherePy[] = "brand = 'Dulux'";
                } elseif ($selectedBrand === 'CATYLAC') {
                    $whereCy[] = "(brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%')";
                    $wherePy[] = "(brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%')";
                }
                if ($search) {
                    $whereCy[] = "(store_name LIKE ? OR sap LIKE ? OR brand LIKE ? OR produk LIKE ?)";
                    $paramsCy[] = "%{$search}%";
                    $paramsCy[] = "%{$search}%";
                    $paramsCy[] = "%{$search}%";
                    $paramsCy[] = "%{$search}%";
                    $wherePy[] = "(store_name LIKE ? OR sap LIKE ? OR brand LIKE ? OR produk LIKE ?)";
                    $paramsPy[] = "%{$search}%";
                    $paramsPy[] = "%{$search}%";
                    $paramsPy[] = "%{$search}%";
                    $paramsPy[] = "%{$search}%";
                }

                $whereCySql = implode(' AND ', $whereCy);
                $wherePySql = implode(' AND ', $wherePy);

                // 1. Total KPI & Brand Breakdown for Current Year
                $kpiStmtCy = $pdo->prepare("
                    SELECT 
                        SUM(volume_liter) as total_vol,
                        COUNT(DISTINCT sap || '---' || store_name) as total_stores,
                        SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_vol,
                        SUM(CASE WHEN (brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%') THEN volume_liter ELSE 0 END) as catylac_vol
                    FROM stock_raw
                    WHERE $whereCySql
                ");
                $kpiStmtCy->execute($paramsCy);
                $kpiCy = $kpiStmtCy->fetch(\PDO::FETCH_ASSOC) ?: [];
                $cyVolume = (float)($kpiCy['total_vol'] ?? 0);
                $cyStores = (int)($kpiCy['total_stores'] ?? 0);
                $duluxVol = (float)($kpiCy['dulux_vol'] ?? 0);
                $catylacVol = (float)($kpiCy['catylac_vol'] ?? 0);

                // Total KPI for Previous Year
                $pyVolume = 0.0;
                $pyStores = 0;
                $pyDuluxVol = 0.0;
                $pyCatylacVol = 0.0;
                if ($has2025) {
                    try {
                        $kpiStmtPy = $pdo->prepare("
                            SELECT 
                                SUM(volume_liter) as total_vol,
                                COUNT(DISTINCT sap || '---' || store_name) as total_stores,
                                SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_vol,
                                SUM(CASE WHEN (brand LIKE '%Catylac%' OR brand LIKE '%Smart Choice%') THEN volume_liter ELSE 0 END) as catylac_vol
                            FROM db25.stock_raw
                            WHERE $wherePySql
                        ");
                        $kpiStmtPy->execute($paramsPy);
                        $kpiPy = $kpiStmtPy->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $pyVolume = (float)($kpiPy['total_vol'] ?? 0);
                        $pyStores = (int)($kpiPy['total_stores'] ?? 0);
                        $pyDuluxVol = (float)($kpiPy['dulux_vol'] ?? 0);
                        $pyCatylacVol = (float)($kpiPy['catylac_vol'] ?? 0);
                    } catch (\Throwable $e) {
                        \Log::warning("Monthly compare db25 KPI query failed: " . $e->getMessage());
                    }
                }

                $growthDiff = $cyVolume - $pyVolume;
                $growthPct = $pyVolume > 0 ? (($cyVolume - $pyVolume) / $pyVolume) * 100 : ($cyVolume > 0 ? 100 : 0);
                $cyAvg = $cyStores > 0 ? ($cyVolume / $cyStores) : 0;
                $pyAvg = $pyStores > 0 ? ($pyVolume / $pyStores) : 0;
                $duluxPct = $cyVolume > 0 ? ($duluxVol / $cyVolume) * 100 : 0;
                $catylacPct = $cyVolume > 0 ? ($catylacVol / $cyVolume) * 100 : 0;

                // 2. Daily Trend Query (Grouped by Day of Month: 1..31)
                $dayExprCy = "CAST(COALESCE(NULLIF(substr(submission_date, 9, 2), ''), NULLIF(substr(tgl_catat, 9, 2), ''), '20') AS INTEGER)";
                $dailyStmtCy = $pdo->prepare("
                    SELECT 
                        $dayExprCy as day_of_month,
                        SUM(volume_liter) as daily_vol
                    FROM stock_raw
                    WHERE $whereCySql
                    GROUP BY day_of_month
                    ORDER BY day_of_month ASC
                ");
                $dailyStmtCy->execute($paramsCy);
                $cyDailyRaw = $dailyStmtCy->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

                $pyDailyRaw = [];
                if ($has2025) {
                    try {
                        $dayExprPy = "CAST(COALESCE(NULLIF(substr(submission_date, 9, 2), ''), NULLIF(substr(tgl_catat, 9, 2), ''), '20') AS INTEGER)";
                        $dailyStmtPy = $pdo->prepare("
                            SELECT 
                                $dayExprPy as day_of_month,
                                SUM(volume_liter) as daily_vol
                            FROM db25.stock_raw
                            WHERE $wherePySql
                            GROUP BY day_of_month
                            ORDER BY day_of_month ASC
                        ");
                        $dailyStmtPy->execute($paramsPy);
                        $pyDailyRaw = $dailyStmtPy->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
                    } catch (\Throwable $e) {
                        \Log::warning("Monthly compare db25 daily query failed: " . $e->getMessage());
                    }
                }

                // Determine days in month (e.g. 28, 29, 30, or 31)
                $daysInMonth = \Carbon\Carbon::create($currentYear, $targetMonth, 1)->daysInMonth;
                $categories = [];
                $days = [];
                $cySeries = [];
                $pySeries = [];
                $dailyTable = [];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dayLabel = sprintf('Tgl %02d', $d);
                    $categories[] = $dayLabel;
                    $days[] = $d;

                    $dCy = (float)($cyDailyRaw[$d] ?? 0);
                    $dPy = (float)($pyDailyRaw[$d] ?? 0);
                    $cySeries[] = round($dCy, 2);
                    $pySeries[] = round($dPy, 2);

                    $delta = $dCy - $dPy;
                    $growth = $dPy > 0 ? (($dCy - $dPy) / $dPy) * 100 : ($dCy > 0 ? 100 : 0);

                    $dailyTable[] = [
                        'day' => $d,
                        'label' => sprintf('%02d %s %d', $d, substr($monthName, 0, 3), $currentYear),
                        'cy_volume' => $dCy,
                        'py_volume' => $dPy,
                        'delta' => $delta,
                        'growth' => $growth,
                    ];
                }

                // 3. Store-level comparison in that specific month (Top 10 Stores)
                $storeStmtCy = $pdo->prepare("
                    SELECT sap, store_name, MIN(region) as region, MIN(area) as area, MIN(derp) as channel, SUM(volume_liter) as cy_vol
                    FROM stock_raw
                    WHERE $whereCySql
                    GROUP BY sap, store_name
                    ORDER BY cy_vol DESC
                ");
                $storeStmtCy->execute($paramsCy);
                $cyStoresAll = $storeStmtCy->fetchAll(\PDO::FETCH_ASSOC);

                $pyStoresMap = [];
                if ($has2025) {
                    try {
                        $storeStmtPy = $pdo->prepare("
                            SELECT sap, store_name, SUM(volume_liter) as py_vol
                            FROM db25.stock_raw
                            WHERE $wherePySql
                            GROUP BY sap, store_name
                        ");
                        $storeStmtPy->execute($paramsPy);
                        while ($r = $storeStmtPy->fetch(\PDO::FETCH_ASSOC)) {
                            $key = $r['sap'] ? 'sap_' . trim($r['sap']) : 'name_' . strtoupper(trim($r['store_name']));
                            $pyStoresMap[$key] = (float)$r['py_vol'];
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Monthly compare db25 store query failed: " . $e->getMessage());
                    }
                }

                $topStoreDetails = [];
                foreach (array_slice($cyStoresAll, 0, 10) as $s) {
                    $sapKey = $s['sap'] ? 'sap_' . trim($s['sap']) : 'name_' . strtoupper(trim($s['store_name']));
                    $sCyVol = (float)$s['cy_vol'];
                    $sPyVol = (float)($pyStoresMap[$sapKey] ?? 0);
                    $sGrowth = $sPyVol > 0 ? (($sCyVol - $sPyVol) / $sPyVol) * 100 : ($sCyVol > 0 ? 100 : 0);

                    $topStoreDetails[] = [
                        'store_name' => $s['store_name'] . ($s['sap'] ? " ({$s['sap']})" : ''),
                        'region' => $s['region'] ?: '-',
                        'area' => $s['area'] ?: '-',
                        'channel' => !empty($s['channel']) ? $s['channel'] : 'Retail',
                        'cy_volume' => $sCyVol,
                        'py_volume' => $sPyVol,
                        'growth' => $sGrowth,
                    ];
                }

                return [
                    'month' => $targetMonth,
                    'month_name' => $monthName,
                    'current_year' => $currentYear,
                    'previous_year' => $previousYear,
                    'selected_brand' => $selectedBrand,
                    'kpi' => [
                        'cy_volume' => $cyVolume,
                        'py_volume' => $pyVolume,
                        'growth' => $growthPct,
                        'growth_diff' => $growthDiff,
                        'cy_stores' => $cyStores,
                        'py_stores' => $pyStores,
                        'cy_avg_per_store' => $cyAvg,
                        'py_avg_per_store' => $pyAvg,
                        'dulux_vol' => $duluxVol,
                        'dulux_pct' => $duluxPct,
                        'catylac_vol' => $catylacVol,
                        'catylac_pct' => $catylacPct,
                    ],
                    'daily_trend' => [
                        'categories' => $categories,
                        'days' => $days,
                        'cy_series' => $cySeries,
                        'py_series' => $pySeries,
                        'table' => $dailyTable,
                    ],
                    'top_stores' => $topStoreDetails,
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Stock Monthly Comparison: " . $e->getMessage());
                return $emptyResult;
            }
        });
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
            $activeMonths[$m] = $monthNames[$m] . ' ' . $endYear;
        }

        $cacheKey = 'offtake_dash_v2_' . md5($template->id . '_' . $sMonth . '_' . $eMonth . '_' . $endYear . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search . '_' . $offtakePage . '_' . $rawPage);

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sMonth, $eMonth, $activeMonths, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $offtakePage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = ["month BETWEEN ? AND ?"];
                $params = [$sMonth, $eMonth];

                if ($selectedRegion) {
                    $areaToRsm = $this->getDuluxAreaToRsmMap();
                    $matchingAreas = [];
                    foreach ($areaToRsm as $aName => $rsmName) {
                        if (strcasecmp($rsmName, $selectedRegion) === 0) {
                            $matchingAreas[] = $aName;
                        }
                    }
                    if (!empty($matchingAreas)) {
                        $placeholders = implode(',', array_fill(0, count($matchingAreas), '?'));
                        $where[] = "(UPPER(TRIM(area)) IN ($placeholders) OR region = ?)";
                        foreach ($matchingAreas as $ma) {
                            $params[] = $ma;
                        }
                        $params[] = $selectedRegion;
                    } else {
                        $where[] = "region = ?";
                        $params[] = $selectedRegion;
                    }
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
     * Calculate Dulux Offtake YTD Comparison (Current Year vs Previous Year)
     */
    protected function calculateOfftakeYtdData($template, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $search)
    {
        $p26 = storage_path('app/dulux_data/offtake_2026.sqlite');
        $p25 = storage_path('app/dulux_data/offtake_2025.sqlite');
        $gz25 = storage_path('app/dulux_data/offtake_2025.sqlite.gz');

        // Auto-extract 2025 if missing or corrupted (< 50MB) but .gz exists
        if (!file_exists($p25) || filesize($p25) < 50000000) {
            if (file_exists($gz25)) {
                try {
                    $zp = gzopen($gz25, 'rb');
                    $tmpPath = $p25 . '.tmp.' . uniqid();
                    $fp = fopen($tmpPath, 'wb');
                    if ($zp && $fp) {
                        while (!gzeof($zp)) {
                            fwrite($fp, gzread($zp, 524288));
                        }
                        gzclose($zp);
                        fclose($fp);
                        @rename($tmpPath, $p25);
                        @chmod($p25, 0666);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Auto-extraction of offtake_2025.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($p26)) {
            return [
                'details' => [],
                'total' => ['brand' => 'Total Akzonobel', 'cy_volume' => 0, 'py_volume' => 0, 'growth' => 0, 'percentage' => 100],
                'monthly_trend' => ['categories' => [], 'cy_total' => [], 'py_total' => []],
                'stores' => ['total' => ['count' => 0, 'cy_volume' => 0, 'py_volume' => 0, 'growth' => 0], 'top10' => [], 'details' => []]
            ];
        }

        $eMonth = max(1, min(12, (int)$endMonth));
        $cacheKey = 'offtake_ytd_v3_' . md5($template->id . '_' . $eMonth . '_' . $endYear . '_' . $selectedRegion . '_' . $selectedAreaId . '_' . $selectedLocationId . '_' . $search);

        return Cache::remember($cacheKey, 600, function() use ($p26, $p25, $eMonth, $selectedRegion, $selectedAreaId, $selectedLocationId, $search) {
            try {
                $pdo = new \PDO("sqlite:" . $p26);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $has2025 = file_exists($p25);
                if ($has2025) {
                    $pdo->exec("ATTACH DATABASE '{$p25}' AS db25");
                }

                $whereCy = ["month BETWEEN 1 AND ?"];
                $paramsCy = [$eMonth];
                $wherePy = ["month BETWEEN 1 AND ?"];
                $paramsPy = [$eMonth];

                if ($selectedRegion) {
                    $areaToRsm = $this->getDuluxAreaToRsmMap();
                    $matchingAreas = [];
                    foreach ($areaToRsm as $aName => $rsmName) {
                        if (strcasecmp($rsmName, $selectedRegion) === 0) {
                            $matchingAreas[] = $aName;
                        }
                    }
                    if (!empty($matchingAreas)) {
                        $placeholders = implode(',', array_fill(0, count($matchingAreas), '?'));
                        $whereCy[] = "(UPPER(TRIM(area)) IN ($placeholders) OR region = ?)";
                        $wherePy[] = "(UPPER(TRIM(area)) IN ($placeholders) OR region = ?)";
                        foreach ($matchingAreas as $ma) {
                            $paramsCy[] = $ma;
                            $paramsPy[] = $ma;
                        }
                        $paramsCy[] = $selectedRegion;
                        $paramsPy[] = $selectedRegion;
                    } else {
                        $whereCy[] = "region = ?";
                        $paramsCy[] = $selectedRegion;
                        $wherePy[] = "region = ?";
                        $paramsPy[] = $selectedRegion;
                    }
                }
                if ($selectedAreaId) {
                    $whereCy[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $paramsCy[] = $selectedAreaId;
                    $wherePy[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $paramsPy[] = $selectedAreaId;
                }
                if ($selectedLocationId) {
                    $whereCy[] = "name_store = ?";
                    $paramsCy[] = $selectedLocationId;
                    $wherePy[] = "name_store = ?";
                    $paramsPy[] = $selectedLocationId;
                }
                if ($search) {
                    $whereCy[] = "(name_store LIKE ? OR sap LIKE ?)";
                    $paramsCy[] = "%{$search}%";
                    $paramsCy[] = "%{$search}%";
                    $wherePy[] = "(name_store LIKE ? OR sap LIKE ?)";
                    $paramsPy[] = "%{$search}%";
                    $paramsPy[] = "%{$search}%";
                }

                $whereCySql = implode(' AND ', $whereCy);
                $wherePySql = implode(' AND ', $wherePy);

                // 1. Product/Brand Comparison
                $brandStmtCy = $pdo->prepare("
                    SELECT 
                        CASE WHEN brand LIKE '%Catylac%' THEN 'Offtake Catylac' ELSE 'Offtake Dulux' END as brand_group,
                        SUM(volume_liter) as vol
                    FROM offtake_raw
                    WHERE $whereCySql
                    GROUP BY brand_group
                ");
                $brandStmtCy->execute($paramsCy);
                $cyBrands = $brandStmtCy->fetchAll(\PDO::FETCH_KEY_PAIR);

                $pyBrands = [];
                if ($has2025) {
                    try {
                        $brandStmtPy = $pdo->prepare("
                            SELECT 
                                CASE WHEN brand LIKE '%Catylac%' THEN 'Offtake Catylac' ELSE 'Offtake Dulux' END as brand_group,
                                SUM(volume_liter) as vol
                            FROM db25.offtake_raw
                            WHERE $wherePySql
                            GROUP BY brand_group
                        ");
                        $brandStmtPy->execute($paramsPy);
                        $pyBrands = $brandStmtPy->fetchAll(\PDO::FETCH_KEY_PAIR);
                    } catch (\Throwable $e) {
                        \Log::warning("Offtake YTD db25 brand query failed: " . $e->getMessage());
                    }
                }

                $allBrands = ['Offtake Dulux', 'Offtake Catylac'];
                $details = [];
                $totalCy = 0;
                $totalPy = 0;

                foreach ($allBrands as $brand) {
                    $cyVol = (float)($cyBrands[$brand] ?? 0);
                    $pyVol = (float)($pyBrands[$brand] ?? 0);
                    $totalCy += $cyVol;
                    $totalPy += $pyVol;
                    $growth = $pyVol > 0 ? (($cyVol - $pyVol) / $pyVol) * 100 : ($cyVol > 0 ? 100 : 0);
                    $details[] = [
                        'brand' => $brand,
                        'cy_volume' => $cyVol,
                        'py_volume' => $pyVol,
                        'growth' => $growth,
                        'percentage' => 0
                    ];
                }

                foreach ($details as &$d) {
                    $d['percentage'] = $totalCy > 0 ? ($d['cy_volume'] / $totalCy) * 100 : 0;
                }
                unset($d);

                $totalGrowth = $totalPy > 0 ? (($totalCy - $totalPy) / $totalPy) * 100 : ($totalCy > 0 ? 100 : 0);
                $totalRow = [
                    'brand' => 'Total Akzonobel',
                    'cy_volume' => $totalCy,
                    'py_volume' => $totalPy,
                    'growth' => $totalGrowth,
                    'percentage' => 100
                ];

                // 1b. Monthly Trend Comparison (Jan s/d $eMonth)
                $monthStmtCy = $pdo->prepare("
                    SELECT 
                        month,
                        SUM(volume_liter) as total_vol,
                        SUM(CASE WHEN brand LIKE '%Catylac%' THEN volume_liter ELSE 0 END) as catylac_vol,
                        SUM(CASE WHEN brand NOT LIKE '%Catylac%' THEN volume_liter ELSE 0 END) as dulux_vol
                    FROM offtake_raw
                    WHERE $whereCySql
                    GROUP BY month
                    ORDER BY month ASC
                ");
                $monthStmtCy->execute($paramsCy);
                $cyMonthlyMap = [];
                while ($mr = $monthStmtCy->fetch(\PDO::FETCH_ASSOC)) {
                    $cyMonthlyMap[(int)$mr['month']] = [
                        'total' => (float)$mr['total_vol'],
                        'dulux' => (float)$mr['dulux_vol'],
                        'catylac' => (float)$mr['catylac_vol'],
                    ];
                }

                $pyMonthlyMap = [];
                if ($has2025) {
                    try {
                        $monthStmtPy = $pdo->prepare("
                            SELECT 
                                month,
                                SUM(volume_liter) as total_vol,
                                SUM(CASE WHEN brand LIKE '%Catylac%' THEN volume_liter ELSE 0 END) as catylac_vol,
                                SUM(CASE WHEN brand NOT LIKE '%Catylac%' THEN volume_liter ELSE 0 END) as dulux_vol
                            FROM db25.offtake_raw
                            WHERE $wherePySql
                            GROUP BY month
                            ORDER BY month ASC
                        ");
                        $monthStmtPy->execute($paramsPy);
                        while ($mr = $monthStmtPy->fetch(\PDO::FETCH_ASSOC)) {
                            $pyMonthlyMap[(int)$mr['month']] = [
                                'total' => (float)$mr['total_vol'],
                                'dulux' => (float)$mr['dulux_vol'],
                                'catylac' => (float)$mr['catylac_vol'],
                            ];
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Offtake YTD db25 monthly trend failed: " . $e->getMessage());
                    }
                }

                $monthLabels = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                $trendCategories = [];
                $cyTotalSeries = [];
                $pyTotalSeries = [];
                $cyDuluxSeries = [];
                $pyDuluxSeries = [];
                $cyCatylacSeries = [];
                $pyCatylacSeries = [];

                for ($m = 1; $m <= $eMonth; $m++) {
                    $trendCategories[] = $monthLabels[$m] ?? "M{$m}";
                    $cyTotalSeries[] = round($cyMonthlyMap[$m]['total'] ?? 0, 2);
                    $pyTotalSeries[] = round($pyMonthlyMap[$m]['total'] ?? 0, 2);
                    $cyDuluxSeries[] = round($cyMonthlyMap[$m]['dulux'] ?? 0, 2);
                    $pyDuluxSeries[] = round($pyMonthlyMap[$m]['dulux'] ?? 0, 2);
                    $cyCatylacSeries[] = round($cyMonthlyMap[$m]['catylac'] ?? 0, 2);
                    $pyCatylacSeries[] = round($pyMonthlyMap[$m]['catylac'] ?? 0, 2);
                }

                // 2. Store Comparison
                $storeStmtCy = $pdo->prepare("
                    SELECT sap, name_store, MIN(region) as region, MIN(area) as area, MIN(category_store) as channel, SUM(volume_liter) as cy_vol
                    FROM offtake_raw
                    WHERE $whereCySql
                    GROUP BY sap, name_store
                ");
                $storeStmtCy->execute($paramsCy);
                $cyStoresRaw = $storeStmtCy->fetchAll(\PDO::FETCH_ASSOC);

                $pyStoresMap = [];
                if ($has2025) {
                    try {
                        $storeStmtPy = $pdo->prepare("
                            SELECT sap, name_store, SUM(volume_liter) as py_vol
                            FROM db25.offtake_raw
                            WHERE $wherePySql
                            GROUP BY sap, name_store
                        ");
                        $storeStmtPy->execute($paramsPy);
                        while ($r = $storeStmtPy->fetch(\PDO::FETCH_ASSOC)) {
                            $key = $r['sap'] ? 'sap_' . trim($r['sap']) : 'name_' . strtoupper(trim($r['name_store']));
                            $pyStoresMap[$key] = (float)$r['py_vol'];
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Offtake YTD db25 store query failed: " . $e->getMessage());
                    }
                }

                $storeDetails = [];
                $totalStoreCy = 0;
                $totalStorePy = 0;

                foreach ($cyStoresRaw as $s) {
                    $sapKey = $s['sap'] ? 'sap_' . trim($s['sap']) : 'name_' . strtoupper(trim($s['name_store']));
                    $cyVol = (float)$s['cy_vol'];
                    $pyVol = (float)($pyStoresMap[$sapKey] ?? 0);
                    $growth = $pyVol > 0 ? (($cyVol - $pyVol) / $pyVol) * 100 : ($cyVol > 0 ? 100 : 0);
                    $totalStoreCy += $cyVol;
                    $totalStorePy += $pyVol;

                    $storeDetails[] = [
                        'store_name' => $s['name_store'] . ($s['sap'] ? " ({$s['sap']})" : ''),
                        'region' => $s['region'] ?: '-',
                        'area' => $s['area'] ?: '-',
                        'channel' => !empty($s['channel']) ? $s['channel'] : 'Retail',
                        'cy_volume' => $cyVol,
                        'py_volume' => $pyVol,
                        'growth' => $growth,
                        'percentage' => 0
                    ];
                }

                usort($storeDetails, fn($a, $b) => $b['cy_volume'] <=> $a['cy_volume']);

                foreach ($storeDetails as &$sd) {
                    $sd['percentage'] = $totalStoreCy > 0 ? ($sd['cy_volume'] / $totalStoreCy) * 100 : 0;
                }
                unset($sd);

                $top10 = array_slice($storeDetails, 0, 10);
                $overallStoreGrowth = $totalStorePy > 0 ? (($totalStoreCy - $totalStorePy) / $totalStorePy) * 100 : ($totalStoreCy > 0 ? 100 : 0);

                return [
                    'details' => $details,
                    'total' => $totalRow,
                    'monthly_trend' => [
                        'categories' => $trendCategories,
                        'cy_total' => $cyTotalSeries,
                        'py_total' => $pyTotalSeries,
                        'cy_dulux' => $cyDuluxSeries,
                        'py_dulux' => $pyDuluxSeries,
                        'cy_catylac' => $cyCatylacSeries,
                        'py_catylac' => $pyCatylacSeries,
                    ],
                    'stores' => [
                        'total' => [
                            'count' => count($storeDetails),
                            'cy_volume' => $totalStoreCy,
                            'py_volume' => $totalStorePy,
                            'growth' => $overallStoreGrowth
                        ],
                        'top10' => $top10,
                        'details' => $storeDetails
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Offtake YTD: " . $e->getMessage());
                return [
                    'details' => [],
                    'total' => ['brand' => 'Total Akzonobel', 'cy_volume' => 0, 'py_volume' => 0, 'growth' => 0, 'percentage' => 100],
                    'monthly_trend' => ['categories' => [], 'cy_total' => [], 'py_total' => []],
                    'stores' => ['total' => ['count' => 0, 'cy_volume' => 0, 'py_volume' => 0, 'growth' => 0], 'top10' => [], 'details' => []]
                ];
            }
        });
    }

    /**
     * Calculate CBP Analytics for Dashboard (1) & Dashboard (2) and Raw Data
     */
    protected function calculateCbpDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $search, $rawPage = 1, $rawPerPage = 50)
    {
        $selectedYear = (int)($endYear ?: $startYear ?: 2026);
        if ($selectedYear <= 0) $selectedYear = 2026;

        $sqlitePath = storage_path("app/dulux_data/cbp_{$selectedYear}.sqlite");
        $gzPath = storage_path("app/dulux_data/cbp_{$selectedYear}.sqlite.gz");

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
                    \Log::error("Auto-extraction of cbp_{$selectedYear}.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($sqlitePath)) {
            $fallbackPath = storage_path('app/dulux_data/cbp_2026.sqlite');
            if (file_exists($fallbackPath)) {
                $sqlitePath = $fallbackPath;
            }
        }

        // Prepare Months (Full range from sMonth to eMonth, 1..12 without artificial caps)
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
            $dateObj = Carbon::create($selectedYear, $m, 1);
            $months[$m] = [
                'm' => $m,
                'short' => $monthNames[$m] ?? "Bln $m",
                'label' => ($monthNames[$m] ?? "Bln $m") . ' ' . $selectedYear,
                'date_header' => strtoupper($dateObj->translatedFormat('F Y'))
            ];
        }

        $startDate = Carbon::createFromDate($selectedYear, $sMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($selectedYear, $eMonth, 1)->endOfMonth();

        // 1. Fetch Live Submissions from PostgreSQL
        $liveRows = [];
        $liveUniqueStores = [];
        try {
            $liveQuery = $this->getLiveSubmissionsQuery($template, $startDate, $endDate, $selectedRegion, $selectedAreaId, $selectedLocationId, $search);
            $liveSubs = $liveQuery->get();

            foreach ($liveSubs as $sub) {
                $valMap = [];
                foreach ($sub->values as $v) {
                    $valMap[$v->field_name] = $v->value_number ?? $v->value_text;
                }

                $productName = trim((string)($valMap['subbrand_produk'] ?? $valMap['product'] ?? $valMap['nama_produk'] ?? ''));
                $brandCat = trim((string)($valMap['brand_cat'] ?? $valMap['brand'] ?? ''));
                $category = trim((string)($valMap['kategori_produk'] ?? $valMap['category'] ?? 'Dulux Interior'));

                $isAn = (stripos($brandCat, 'Dulux') !== false || stripos($brandCat, 'Akzo') !== false || stripos($brandCat, 'AN') !== false || stripos($productName, 'Ambiance') !== false || stripos($productName, 'Pentalite') !== false || stripos($productName, 'Catylac') !== false || stripos($productName, 'Weathershield') !== false || stripos($productName, 'Easy Clean') !== false || stripos($productName, 'Aquashield') !== false || stripos($productName, 'V-Gloss') !== false);
                $brandGroup = $isAn ? 'AN' : ($brandCat ?: 'Kompetitor');

                $pTin = (float)($valMap['harga_tin_rp'] ?? 0);
                $lTin = (float)($valMap['harga_terendah_tin_rp'] ?? $pTin);
                $rTin = (string)($valMap['alasan_promo_keterangan'] ?? '');

                $pGalon = (float)($valMap['harga_galon_rp'] ?? 0);
                $lGalon = (float)($valMap['harga_terendah_galon_rp'] ?? $pGalon);
                $rGalon = (string)($valMap['alasan_promo_keterangan'] ?? '');

                $pPail = (float)($valMap['harga_pail_rp'] ?? 0);
                $lPail = (float)($valMap['harga_terendah_pail_rp'] ?? $pPail);
                $rPail = (string)($valMap['alasan_promo_keterangan'] ?? '');

                $subMonth = (int)$sub->submitted_at->format('n');
                $storeName = $sub->workLocation?->name ?? 'Toko Tidak Terdaftar';
                $liveUniqueStores[$storeName] = true;
                $branchName = $sub->workLocation?->branch?->name ?? '-';
                $cleanBranch = strtoupper(trim($branchName));
                $rsmArea = $this->mapAreaToRsm($cleanBranch, $sub->workLocation?->region ?? 'RSM JAWA TIMUR');
                $tlName = $sub->employee?->reportingTo?->name ?? $sub->employee?->supervisor_name ?? ($sub->employee?->name . ' (Demo)') ?? '-';
                $sapMember = $sub->workLocation?->code ?? '-';

                $itemCode = md5(strtoupper(trim($storeName)) . '_' . strtoupper(trim($productName ?: 'Dulux')));

                $liveRows[] = [
                    'submission_id' => $sub->id,
                    'submission_code' => $sub->submission_code,
                    'code' => $itemCode,
                    'regional' => $rsmArea,
                    'sap_member' => $sapMember,
                    'sap_gab' => '-',
                    'name_store' => $storeName,
                    'tl_name' => $tlName,
                    'area' => $branchName,
                    'rsm_area' => $rsmArea,
                    'class' => '-',
                    'store_type' => '-',
                    'product' => $productName ?: 'Dulux Product',
                    'category' => $category,
                    'product_group' => $isAn ? 'Dulux' : $brandCat,
                    'brand' => $brandGroup,
                    'brand_raw' => $brandCat,
                    'month' => $subMonth,
                    'trans_date' => $sub->submitted_at->format('Y-m-d'),
                    'price_tin' => $pTin,
                    'lowest_tin' => $lTin,
                    'reason_tin' => $rTin,
                    'price_galon' => $pGalon,
                    'lowest_galon' => $lGalon,
                    'reason_galon' => $rGalon,
                    'price_pail' => $pPail,
                    'lowest_pail' => $lPail,
                    'reason_pail' => $rPail,
                    'is_live' => true,
                ];
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to query CBP live submissions: " . $e->getMessage());
        }

        // 2. Sections Configuration
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

        // 3. Query SQLite Data (for historical records)
        $sqliteKpis = ['total_records' => 0, 'unique_stores' => 0, 'sum_an_galon' => 0, 'count_an_galon' => 0, 'sum_comp_galon' => 0, 'count_comp_galon' => 0];
        $trendSeries = [
            'AkzoNobel (Dulux)' => [],
            'Jotun' => [],
            'Nippon Paint' => [],
            'Avian / Aquaproof' => [],
            'Mowilex' => [],
        ];
        $sectionProducts = ['d1' => [], 'd2' => []];
        $sqliteRawRows = [];
        $sqliteRawTotal = 0;

        if (file_exists($sqlitePath)) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
                $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;

                $whereClauses = ["month BETWEEN ? AND ?"];
                $params = [min(array_keys($months)), max(array_keys($months))];

                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $whereClauses[] = "(rsm_area IN ($inPlaceholders) OR regional = ?)";
                    foreach ($rsmVariants as $rv) {
                        $params[] = $rv;
                    }
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

                // SQLite KPI
                $kpiSql = "
                    SELECT COUNT(*) as total_records,
                           COUNT(DISTINCT name_store) as unique_stores,
                           SUM(CASE WHEN brand = 'AN' AND price_galon >= 1000 AND price_galon <= 5000000 THEN price_galon ELSE 0 END) as sum_an_galon,
                           SUM(CASE WHEN brand = 'AN' AND price_galon >= 1000 AND price_galon <= 5000000 THEN 1 ELSE 0 END) as count_an_galon,
                           SUM(CASE WHEN brand != 'AN' AND brand != '' AND price_galon >= 1000 AND price_galon <= 5000000 THEN price_galon ELSE 0 END) as sum_comp_galon,
                           SUM(CASE WHEN brand != 'AN' AND brand != '' AND price_galon >= 1000 AND price_galon <= 5000000 THEN 1 ELSE 0 END) as count_comp_galon
                    FROM cbp_raw
                    WHERE $whereSql
                ";
                $stmt = $pdo->prepare($kpiSql);
                $stmt->execute($params);
                $kRow = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($kRow) {
                    $sqliteKpis['total_records'] = (int)($kRow['total_records'] ?? 0);
                    $sqliteKpis['unique_stores'] = (int)($kRow['unique_stores'] ?? 0);
                    $sqliteKpis['sum_an_galon'] = (float)($kRow['sum_an_galon'] ?? 0);
                    $sqliteKpis['count_an_galon'] = (int)($kRow['count_an_galon'] ?? 0);
                    $sqliteKpis['sum_comp_galon'] = (float)($kRow['sum_comp_galon'] ?? 0);
                    $sqliteKpis['count_comp_galon'] = (int)($kRow['count_comp_galon'] ?? 0);
                }

                // SQLite Trends
                $trendSql = "
                    SELECT 
                        CASE 
                            WHEN brand = 'AN' OR brand LIKE '%Dulux%' OR brand LIKE '%Akzo%' THEN 'AkzoNobel (Dulux)'
                            WHEN brand LIKE '%Jotun%' THEN 'Jotun'
                            WHEN brand IN ('Nippon', 'Nippon Paint') OR brand LIKE '%Nippon%' THEN 'Nippon Paint'
                            WHEN brand IN ('Avian', 'Aquaproof') OR brand LIKE '%Avian%' OR brand LIKE '%Aquaproof%' THEN 'Avian / Aquaproof'
                            WHEN brand LIKE '%Mowilex%' THEN 'Mowilex'
                            ELSE 'Lainnya'
                        END as brand_group,
                        month,
                        AVG(price_galon) as avg_price
                    FROM cbp_raw
                    WHERE category IN ('Super Premium Interior', 'Premium Interior', 'Dulux Interior', 'Super Premium Exterior', 'Premium Exterior', 'Mass Interior', 'Washable Segment')
                      AND price_galon >= 1000 AND price_galon <= 5000000
                      AND $whereSql
                    GROUP BY brand_group, month
                    ORDER BY brand_group, month
                ";
                $stmt = $pdo->prepare($trendSql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $tr) {
                    $bg = $tr['brand_group'];
                    if (isset($trendSeries[$bg])) {
                        $trendSeries[$bg][$tr['month']] = round((float)$tr['avg_price'], 0);
                    }
                }

                // SQLite Sections
                foreach (['d1', 'd2'] as $dKey) {
                    foreach ($sectionsConfig[$dKey] as $sKey => $cfg) {
                        $metricCol = $cfg['metric'];
                        $catQ = $cfg['category_query'];

                        $sqlSec = "
                            SELECT product, brand, month,
                                   AVG($metricCol) as avg_price,
                                   COUNT(*) as cnt
                            FROM cbp_raw
                            WHERE $catQ AND $metricCol >= 1000 AND $metricCol <= 25000000 AND $whereSql
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
                        $sectionProducts[$dKey][$sKey] = $prods;
                    }
                }

                // SQLite Raw Data
                $rawWhereClauses = ["month BETWEEN ? AND ?"];
                $rawParams = [$sMonth, $eMonth];
                if ($selectedRegion) {
                    $rawWhereClauses[] = "regional = ?";
                    $rawParams[] = $selectedRegion;
                }
                if ($selectedAreaName) {
                    $rawWhereClauses[] = "(UPPER(area) LIKE ? OR UPPER(rsm_area) LIKE ?)";
                    $rawParams[] = "%" . strtoupper($selectedAreaName) . "%";
                    $rawParams[] = "%" . strtoupper($selectedAreaName) . "%";
                }
                if ($selectedStoreName) {
                    $rawWhereClauses[] = "name_store LIKE ?";
                    $rawParams[] = "%$selectedStoreName%";
                }
                if ($search) {
                    $rawWhereClauses[] = "(product LIKE ? OR brand LIKE ? OR name_store LIKE ? OR sap_member LIKE ? OR sap_gab LIKE ? OR tl_name LIKE ?)";
                    $rawParams[] = "%$search%";
                    $rawParams[] = "%$search%";
                    $rawParams[] = "%$search%";
                    $rawParams[] = "%$search%";
                    $rawParams[] = "%$search%";
                    $rawParams[] = "%$search%";
                }
                $rawWhereSql = implode(" AND ", $rawWhereClauses);

                $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT code) FROM cbp_raw WHERE $rawWhereSql");
                $countStmt->execute($rawParams);
                $sqliteRawTotal = (int)$countStmt->fetchColumn();

                $rawSql = "
                    SELECT code, regional, sap_member, sap_gab, name_store, tl_name, area, rsm_area, class, store_type, product, category, product_group
                    FROM cbp_raw
                    WHERE $rawWhereSql
                    GROUP BY code
                    ORDER BY regional, area, name_store, product
                ";
                $rawStmt = $pdo->prepare($rawSql);
                $rawStmt->execute($rawParams);
                $sqliteRawRows = $rawStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Populate prices for sqlite rows
                $codes = array_column($sqliteRawRows, 'code');
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
                    foreach ($sqliteRawRows as &$it) {
                        $it['monthly_prices'] = $pivoted[$it['code']] ?? [];
                    }
                    unset($it);
                }
            } catch (\Throwable $e) {
                \Log::error("SQLite query error in CBP: " . $e->getMessage());
            }
        }

        // 4. Merge Live Submissions with SQLite Data
        $mergedRawMap = [];
        // First add sqlite rows
        foreach ($sqliteRawRows as $sr) {
            $mergedRawMap[$sr['code']] = $sr;
        }

        // Then merge live submissions
        foreach ($liveRows as $lr) {
            $code = $lr['code'];
            $m = $lr['month'];

            // KPIs
            if ($lr['brand'] === 'AN' && $lr['price_galon'] >= 1000 && $lr['price_galon'] <= 5000000) {
                $sqliteKpis['sum_an_galon'] += $lr['price_galon'];
                $sqliteKpis['count_an_galon']++;
            } elseif ($lr['brand'] !== 'AN' && $lr['price_galon'] >= 1000 && $lr['price_galon'] <= 5000000) {
                $sqliteKpis['sum_comp_galon'] += $lr['price_galon'];
                $sqliteKpis['count_comp_galon']++;
            }

            // Trends
            $bg = ($lr['brand'] === 'AN') ? 'AkzoNobel (Dulux)' : (
                stripos($lr['brand_raw'], 'Jotun') !== false ? 'Jotun' : (
                stripos($lr['brand_raw'], 'Nippon') !== false ? 'Nippon Paint' : (
                (stripos($lr['brand_raw'], 'Avian') !== false || stripos($lr['brand_raw'], 'Aquaproof') !== false) ? 'Avian / Aquaproof' : (
                stripos($lr['brand_raw'], 'Mowilex') !== false ? 'Mowilex' : 'Lainnya'
            ))));
            if (isset($trendSeries[$bg]) && $lr['price_galon'] >= 1000 && $lr['price_galon'] <= 5000000) {
                $trendSeries[$bg][$m] = round($lr['price_galon'], 0);
            }

            // Raw Data Table
            if (!isset($mergedRawMap[$code])) {
                $mergedRawMap[$code] = [
                    'code' => $code,
                    'regional' => $lr['regional'],
                    'sap_member' => $lr['sap_member'],
                    'sap_gab' => $lr['sap_gab'],
                    'name_store' => $lr['name_store'],
                    'tl_name' => $lr['tl_name'],
                    'area' => $lr['area'],
                    'rsm_area' => $lr['rsm_area'],
                    'class' => $lr['class'],
                    'store_type' => $lr['store_type'],
                    'product' => $lr['product'],
                    'category' => $lr['category'],
                    'product_group' => $lr['product_group'],
                    'monthly_prices' => [],
                    'is_live' => true,
                    'submission_code' => $lr['submission_code'],
                    'submission_id' => $lr['submission_id'],
                ];
            }
            $mergedRawMap[$code]['monthly_prices'][$m] = [
                'code' => $code,
                'month' => $m,
                'trans_date' => $lr['trans_date'],
                'price_tin' => $lr['price_tin'],
                'lowest_tin' => $lr['lowest_tin'],
                'reason_tin' => $lr['reason_tin'],
                'price_galon' => $lr['price_galon'],
                'lowest_galon' => $lr['lowest_galon'],
                'reason_galon' => $lr['reason_galon'],
                'price_pail' => $lr['price_pail'],
                'lowest_pail' => $lr['lowest_pail'],
                'reason_pail' => $lr['reason_pail'],
                'is_live' => true,
                'submission_code' => $lr['submission_code'],
                'submission_id' => $lr['submission_id'],
            ];

            // Sections
            foreach (['d1', 'd2'] as $dKey) {
                foreach ($sectionsConfig[$dKey] as $sKey => $cfg) {
                    if (stripos($lr['category'], $sKey) !== false || (stripos($cfg['title'], $lr['category']) !== false)) {
                        $pName = $lr['product'];
                        if (!isset($sectionProducts[$dKey][$sKey][$pName])) {
                            $sectionProducts[$dKey][$sKey][$pName] = [
                                'product' => $pName,
                                'brand' => $lr['brand'],
                                'is_benchmark' => ($pName === $cfg['benchmark_product']),
                                'prices' => [],
                                'indices' => [],
                                'mom_growth' => [],
                                'avg_price' => 0,
                                'avg_index' => 0,
                                'avg_mom' => null,
                            ];
                        }
                        $metricVal = ($cfg['metric'] === 'price_tin') ? $lr['price_tin'] : $lr['price_galon'];
                        if ($metricVal > 0) {
                            $sectionProducts[$dKey][$sKey][$pName]['prices'][$m] = $metricVal;
                        }
                    }
                }
            }
        }

        // Fill null in trendSeries
        foreach ($trendSeries as $bg => &$tMonths) {
            foreach ($months as $m => $mMeta) {
                if (!isset($tMonths[$m]) || $tMonths[$m] <= 0) {
                    $tMonths[$m] = null;
                }
            }
            ksort($tMonths);
        }
        unset($tMonths);

        // Finalize Dashboard 1 and 2 Sections (Indices, MoM, Sort)
        $dashboards = ['d1' => [], 'd2' => []];
        foreach (['d1', 'd2'] as $dKey) {
            foreach ($sectionsConfig[$dKey] as $sKey => $cfg) {
                $prods = $sectionProducts[$dKey][$sKey] ?? [];

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

        // Finalize KPIs
        $avgAn = $sqliteKpis['count_an_galon'] > 0 ? ($sqliteKpis['sum_an_galon'] / $sqliteKpis['count_an_galon']) : 0;
        $avgComp = $sqliteKpis['count_comp_galon'] > 0 ? ($sqliteKpis['sum_comp_galon'] / $sqliteKpis['count_comp_galon']) : 0;
        $totalRecords = $sqliteKpis['total_records'] + count($liveRows);
        $uniqueStores = count(array_unique(array_merge(
            array_column($sqliteRawRows, 'name_store'),
            array_keys($liveUniqueStores)
        )));

        // Finalize Paginated Raw Data
        $allRawRows = array_values($mergedRawMap);
        $totalRawCount = count($allRawRows);
        $rawOffset = ($rawPage - 1) * $rawPerPage;
        $pagedRawRows = array_slice($allRawRows, $rawOffset, $rawPerPage);

        $aggData = [
            'months' => $months,
            'kpis' => [
                'total_records' => $totalRecords,
                'unique_stores' => $uniqueStores,
                'avg_an_galon' => $avgAn,
                'avg_comp_galon' => $avgComp,
                'ratio_index' => ($avgComp > 0) ? (($avgAn / $avgComp) * 100) : 100,
            ],
            'trend_series' => $trendSeries,
            'dashboard1' => $dashboards['d1'],
            'dashboard2' => $dashboards['d2'],
            'raw_data' => [
                'rows' => $pagedRawRows,
                'total' => $totalRawCount,
                'page' => $rawPage,
                'per_page' => $rawPerPage,
                'total_pages' => (int)ceil($totalRawCount / $rawPerPage),
                'from' => $totalRawCount > 0 ? ($rawOffset + 1) : 0,
                'to' => min($rawOffset + $rawPerPage, $totalRawCount),
                'months' => $months
            ]
        ];

        return $aggData;
    }

    /**
     * Calculate Dulux Out of Stock (OOS) Data:
     * - Summary Tab (KPIs + Reason Breakdown Distribution)
     * - Weekly Tab (Weekly Pivot per Store, Product, Base/Color, Kemasan, Alasan OOS)
     * - Raw Submissions Tab (16 Columns matching Excel)
     */
    protected function calculateOosDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedChannel = 'ALL', $showNoOos = false, $search = null, $weeklyPage = 1, $rawPage = 1, $perPage = 50)
    {
        $sqlitePath = storage_path('app/dulux_data/oos_2026.sqlite');
        $gzPath     = storage_path('app/dulux_data/oos_2026.sqlite.gz');

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
                    \Log::error("Auto-extraction of oos_2026.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($sqlitePath)) {
            return [
                'months' => [],
                'weeks' => [],
                'kpis' => ['total_stores' => 0, 'total_oos_cases' => 0, 'no_oos_stores' => 0, 'no_oos_percentage' => 0, 'total_submissions' => 0],
                'reasons' => [],
                'weekly' => ['rows' => [], 'weeks' => [], 'grand_total_cases' => 0, 'total_rows' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
            ];
        }

        $sMonth = max(1, min(12, (int)$startMonth));
        $eMonth = max(1, min(12, (int)$endMonth));
        if ($sMonth > $eMonth) {
            $tmp = $sMonth; $sMonth = $eMonth; $eMonth = $tmp;
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $activeMonths = [];
        for ($m = $sMonth; $m <= $eMonth; $m++) {
            $activeMonths[$m] = $monthNames[$m];
        }

        $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
        $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;

        $cacheKey = 'oos_dash_v2_' . md5($template->id . "_{$sMonth}_{$eMonth}_{$selectedRegion}_{$selectedAreaName}_{$selectedStoreName}_{$selectedChannel}_" . ($showNoOos ? '1' : '0') . "_{$search}_{$weeklyPage}_{$rawPage}_{$perPage}");

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sMonth, $eMonth, $activeMonths, $selectedRegion, $selectedAreaName, $selectedStoreName, $selectedChannel, $showNoOos, $search, $weeklyPage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = ["month BETWEEN ? AND ?"];
                $params = [$sMonth, $eMonth];

                if ($selectedChannel && in_array(strtoupper($selectedChannel), ['LSO', 'SSO'])) {
                    $where[] = "channel = ?";
                    $params[] = strtoupper($selectedChannel);
                }
                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $where[] = "(rsm_area IN ($inPlaceholders) OR region = ?)";
                    foreach ($rsmVariants as $rv) {
                        $params[] = $rv;
                    }
                    $params[] = $selectedRegion;
                }
                if ($selectedAreaName) {
                    $where[] = "UPPER(TRIM(area)) = UPPER(TRIM(?))";
                    $params[] = $selectedAreaName;
                }
                if ($selectedStoreName) {
                    $where[] = "store_name = ?";
                    $params[] = $selectedStoreName;
                }
                if ($search) {
                    $where[] = "(store_name LIKE ? OR sap LIKE ? OR produk LIKE ? OR base_color LIKE ? OR alasan_oos LIKE ?)";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                }
                $whereSql = implode(' AND ', $where);

                // Distinct active weeks
                $weekStmt = $pdo->prepare("SELECT DISTINCT week FROM oos_raw WHERE $whereSql AND week IS NOT NULL ORDER BY CAST(week AS INTEGER) ASC, week ASC");
                $weekStmt->execute($params);
                $activeWeeks = $weekStmt->fetchAll(\PDO::FETCH_COLUMN);

                // 1. KPI Aggregates
                $kpiStmt = $pdo->prepare("
                    SELECT 
                        COUNT(DISTINCT store_name) as total_stores,
                        COUNT(DISTINCT CASE WHEN is_oos = 1 THEN store_name END) as oos_stores,
                        SUM(CASE WHEN is_oos = 1 THEN 1 ELSE 0 END) as oos_incidents,
                        COUNT(*) as total_submissions
                    FROM oos_raw
                    WHERE $whereSql
                ");
                $kpiStmt->execute($params);
                $kpiRow = $kpiStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

                $totalStores = (int)($kpiRow['total_stores'] ?? 0);
                $oosStores = (int)($kpiRow['oos_stores'] ?? 0);
                $noOosStores = max(0, $totalStores - $oosStores);
                $noOosPct = $totalStores > 0 ? round(($noOosStores / $totalStores) * 100, 1) : 0;
                $oosIncidents = (int)($kpiRow['oos_incidents'] ?? 0);
                $totalSubmissions = (int)($kpiRow['total_submissions'] ?? 0);

                $kpis = [
                    'total_stores' => $totalStores,
                    'total_oos_cases' => $oosIncidents,
                    'no_oos_stores' => $noOosStores,
                    'no_oos_percentage' => $noOosPct,
                    'total_submissions' => $totalSubmissions
                ];

                // 2. Reason Breakdown (Only Real OOS: is_oos = 1)
                $reasonWhere = array_merge($where, ["is_oos = 1"]);
                $reasonWhereSql = implode(' AND ', $reasonWhere);
                $reasonStmt = $pdo->prepare("
                    SELECT 
                        COALESCE(NULLIF(TRIM(alasan_oos), ''), 'Lain-lain') as reason,
                        COUNT(DISTINCT store_name) as store_count,
                        COUNT(*) as incident_count
                    FROM oos_raw
                    WHERE $reasonWhereSql
                    GROUP BY reason
                    ORDER BY incident_count DESC
                ");
                $reasonStmt->execute($params);
                $reasonRows = $reasonStmt->fetchAll(\PDO::FETCH_ASSOC);

                $reasons = [];
                foreach ($reasonRows as $r) {
                    $pct = $oosIncidents > 0 ? round(($r['incident_count'] / $oosIncidents) * 100, 1) : 0;
                    $reasons[] = [
                        'reason' => $r['reason'],
                        'store_count' => (int)$r['store_count'],
                        'incident_count' => (int)$r['incident_count'],
                        'percentage' => $pct
                    ];
                }

                // 3. Weekly Pivot Table (Grouped by Store, Product, Base/Color, Kemasan, Alasan OOS)
                $weeklyWhere = array_merge($where, ["is_oos = 1"]);
                $weeklyWhereSql = implode(' AND ', $weeklyWhere);

                $weeklyCountSql = "
                    SELECT COUNT(DISTINCT store_name || '---' || COALESCE(produk,'') || '---' || COALESCE(base_color,'') || '---' || COALESCE(kemasan_size,'') || '---' || COALESCE(alasan_oos,''))
                    FROM oos_raw
                    WHERE $weeklyWhereSql
                ";
                $weeklyCountStmt = $pdo->prepare($weeklyCountSql);
                $weeklyCountStmt->execute($params);
                $totalWeeklyItems = (int)$weeklyCountStmt->fetchColumn();

                $weeklyOffset = ($weeklyPage - 1) * $perPage;

                $weeklyRowsSql = "
                    SELECT 
                        sap, store_name, MIN(region) as region, MIN(area) as area, MIN(channel) as channel,
                        produk, base_color, kemasan_size, alasan_oos,
                        COUNT(*) as grand_total
                    FROM oos_raw
                    WHERE $weeklyWhereSql
                    GROUP BY sap, store_name, produk, base_color, kemasan_size, alasan_oos
                    ORDER BY region ASC, area ASC, store_name ASC, produk ASC
                    LIMIT $perPage OFFSET $weeklyOffset
                ";
                $weeklyRowsStmt = $pdo->prepare($weeklyRowsSql);
                $weeklyRowsStmt->execute($params);
                $weeklyRows = $weeklyRowsStmt->fetchAll(\PDO::FETCH_ASSOC);

                // Fetch week breakdown for these paginated rows
                if (!empty($weeklyRows) && !empty($activeWeeks)) {
                    foreach ($weeklyRows as &$wRow) {
                        $wRow['total_cases'] = (int)$wRow['grand_total'];
                        $wSubWhere = array_merge($weeklyWhere, [
                            "store_name = ?",
                            "COALESCE(produk, '') = ?",
                            "COALESCE(base_color, '') = ?",
                            "COALESCE(kemasan_size, '') = ?",
                            "COALESCE(alasan_oos, '') = ?"
                        ]);
                        $wSubWhereSql = implode(' AND ', $wSubWhere);
                        $wSubParams = array_merge($params, [
                            $wRow['store_name'],
                            $wRow['produk'] ?? '',
                            $wRow['base_color'] ?? '',
                            $wRow['kemasan_size'] ?? '',
                            $wRow['alasan_oos'] ?? ''
                        ]);

                        $wBreakdownStmt = $pdo->prepare("
                            SELECT week, COUNT(*) as cnt
                            FROM oos_raw
                            WHERE $wSubWhereSql
                            GROUP BY week
                        ");
                        $wBreakdownStmt->execute($wSubParams);
                        $wCounts = $wBreakdownStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

                        $weeksData = [];
                        foreach ($activeWeeks as $wk) {
                            $weeksData[$wk] = (int)($wCounts[$wk] ?? 0);
                        }
                        $wRow['weeks'] = $weeksData;
                    }
                    unset($wRow);
                }

                // 4. Raw Submissions (16 Columns matching Excel)
                $rawWhere = $where;
                if (!$showNoOos) {
                    $rawWhere[] = "(is_oos = 1 AND UPPER(TRIM(COALESCE(produk, ''))) != 'NO OOS')";
                }
                $rawWhereSql = implode(' AND ', $rawWhere);

                $rawOffset = ($rawPage - 1) * $perPage;
                $rawCountSql = "SELECT COUNT(*) FROM oos_raw WHERE $rawWhereSql";
                $rawCountStmt = $pdo->prepare($rawCountSql);
                $rawCountStmt->execute($params);
                $totalRaw = (int)$rawCountStmt->fetchColumn();

                $rawSql = "
                    SELECT 
                        channel, submission_code, submission_date, tanggal_oos, week,
                        region, area, rsm_area, account, sap, derp, store_name,
                        produk, base_color, kemasan_size, lama_oos_hari, saran_qty_order, alasan_oos, is_oos
                    FROM oos_raw
                    WHERE $rawWhereSql
                    ORDER BY tanggal_oos DESC, id DESC
                    LIMIT $perPage OFFSET $rawOffset
                ";
                $rawStmt = $pdo->prepare($rawSql);
                $rawStmt->execute($params);
                $rawRows = $rawStmt->fetchAll(\PDO::FETCH_ASSOC);

                return [
                    'months' => $activeMonths,
                    'weeks' => $activeWeeks,
                    'kpis' => $kpis,
                    'reasons' => $reasons,
                    'weekly' => [
                        'rows' => $weeklyRows,
                        'weeks' => $activeWeeks,
                        'grand_total_cases' => $oosIncidents,
                        'total_rows' => $totalWeeklyItems,
                        'total' => $totalWeeklyItems,
                        'page' => $weeklyPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalWeeklyItems / $perPage),
                        'from' => $totalWeeklyItems > 0 ? ($weeklyOffset + 1) : 0,
                        'to' => min($weeklyOffset + $perPage, $totalWeeklyItems),
                    ],
                    'submissions' => [
                        'rows' => $rawRows,
                        'total' => $totalRaw,
                        'page' => $rawPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalRaw / $perPage),
                        'from' => $totalRaw > 0 ? ($rawOffset + 1) : 0,
                        'to' => min($rawOffset + $perPage, $totalRaw),
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate OOS Dashboard: " . $e->getMessage());
                return [
                    'months' => $activeMonths,
                    'weeks' => [],
                    'kpis' => ['total_stores' => 0, 'total_oos_cases' => 0, 'no_oos_stores' => 0, 'no_oos_percentage' => 0, 'total_submissions' => 0],
                    'reasons' => [],
                    'weekly' => ['rows' => [], 'weeks' => [], 'grand_total_cases' => 0, 'total_rows' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
                ];
            }
        });
    }

    /**
     * Kalkulasi Dashboard Eksekutif Daily Maintenance Dulux (2025 - 2026)
     * Langsung dari SQLite db terindeks storage/app/dulux_data/daily_maintenance.sqlite
     */
    protected function calculateDailyMaintenanceDashboardData($template, $startMonth, $startYear, $endMonth, $endYear, $selectedRegion, $selectedAreaId, $selectedLocationId, $selectedMachineType = '', $selectedCategory = '', $search = null, $storePage = 1, $rawPage = 1, $perPage = 50)
    {
        $sqlitePath = storage_path('app/dulux_data/daily_maintenance.sqlite');
        $gzPath     = storage_path('app/dulux_data/daily_maintenance.sqlite.gz');

        if (!file_exists($sqlitePath) || filesize($sqlitePath) < 1000000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                    \Log::error("Auto-extraction of daily_maintenance.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        $sMonth = max(1, min(12, (int)$startMonth));
        $eMonth = max(1, min(12, (int)$endMonth));
        $sYear  = (int)($startYear ?: 2026);
        $eYear  = (int)($endYear ?: 2026);

        $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
        $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;

        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        $activeMonths = [];
        for ($m = $sMonth; $m <= $eMonth; $m++) {
            $activeMonths[$m] = $monthNames[$m];
        }

        $cacheKey = 'dm_dash_v3_' . md5($template->id . "_{$sYear}_{$sMonth}_{$eYear}_{$eMonth}_{$selectedRegion}_{$selectedAreaName}_{$selectedStoreName}_{$selectedMachineType}_{$selectedCategory}_{$search}_{$storePage}_{$rawPage}_{$perPage}");

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sYear, $sMonth, $eYear, $eMonth, $activeMonths, $selectedRegion, $selectedAreaName, $selectedStoreName, $selectedMachineType, $selectedCategory, $search, $storePage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = [];
                $params = [];

                if ($sYear === $eYear) {
                    $where[] = "year = ?";
                    $params[] = $sYear;
                    $where[] = "month >= ? AND month <= ?";
                    $params[] = $sMonth;
                    $params[] = $eMonth;
                } else {
                    $where[] = "((year = ? AND month >= ?) OR (year = ? AND month <= ?) OR (year > ? AND year < ?))";
                    $params[] = $sYear; $params[] = $sMonth;
                    $params[] = $eYear; $params[] = $eMonth;
                    $params[] = $sYear; $params[] = $eYear;
                }

                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $where[] = "rsm_area IN ($inPlaceholders)";
                    foreach ($rsmVariants as $rv) {
                        $params[] = $rv;
                    }
                }
                if ($selectedAreaName) {
                    $where[] = "area = ?";
                    $params[] = $selectedAreaName;
                }
                if ($selectedStoreName) {
                    $where[] = "store_name = ?";
                    $params[] = $selectedStoreName;
                }
                if ($selectedMachineType) {
                    $where[] = "machine_type = ?";
                    $params[] = $selectedMachineType;
                }
                if ($selectedCategory) {
                    $where[] = "category = ?";
                    $params[] = $selectedCategory;
                }
                if ($search) {
                    $where[] = "(store_name LIKE ? OR sap_code LIKE ? OR machine_no LIKE ? OR dc_name LIKE ? OR tl_name LIKE ?)";
                    $like = "%{$search}%";
                    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
                }

                $whereSql = implode(' AND ', $where);

                // 1. KPIs
                $kpiSql = "
                    SELECT 
                        COUNT(*) as total_submissions,
                        COUNT(DISTINCT store_name) as total_stores,
                        COUNT(DISTINCT machine_no) as total_machines,
                        SUM(tinta_ok) as sum_tinta,
                        SUM(CASE WHEN d200_nozzle_ok = 1 OR discovery_brush_ok = 1 OR manual_nozzle_ok = 1 THEN 1 ELSE 0 END) as sum_nozzle,
                        SUM(CASE WHEN mix2win_steps_ok >= 10 THEN 1 ELSE 0 END) as sum_mix2win,
                        SUM(pembersihan_all_ok) as sum_pembersihan
                    FROM dm_raw
                    WHERE $whereSql
                ";
                $stmt = $pdo->prepare($kpiSql);
                $stmt->execute($params);
                $kpiRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                $totSub = (int)($kpiRow['total_submissions'] ?? 0);
                $kpis = [
                    'total_submissions' => $totSub,
                    'total_stores' => (int)($kpiRow['total_stores'] ?? 0),
                    'total_machines' => (int)($kpiRow['total_machines'] ?? 0),
                    'tinta_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_tinta'] / $totSub) * 100, 1) : 0,
                    'nozzle_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_nozzle'] / $totSub) * 100, 1) : 0,
                    'mix2win_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_mix2win'] / $totSub) * 100, 1) : 0,
                    'pembersihan_rate' => $totSub > 0 ? round(((int)$kpiRow['sum_pembersihan'] / $totSub) * 100, 1) : 0,
                ];

                // 2. Breakdown per Machine Type
                $mTypeSql = "
                    SELECT 
                        machine_type,
                        COUNT(*) as submissions,
                        COUNT(DISTINCT store_name) as stores,
                        COUNT(DISTINCT machine_no) as machines,
                        ROUND(AVG(tinta_ok) * 100, 1) as avg_tinta,
                        ROUND(AVG(pembersihan_all_ok) * 100, 1) as avg_clean
                    FROM dm_raw
                    WHERE $whereSql
                    GROUP BY machine_type
                    ORDER BY submissions DESC
                ";
                $stmt = $pdo->prepare($mTypeSql);
                $stmt->execute($params);
                $byMachine = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 3. Breakdown per Category
                $catSql = "
                    SELECT 
                        category,
                        COUNT(*) as submissions,
                        COUNT(DISTINCT store_name) as stores,
                        COUNT(DISTINCT machine_no) as machines,
                        ROUND(AVG(tinta_ok) * 100, 1) as avg_tinta,
                        ROUND(AVG(pembersihan_all_ok) * 100, 1) as avg_clean
                    FROM dm_raw
                    WHERE $whereSql
                    GROUP BY category
                    ORDER BY submissions DESC
                ";
                $stmt = $pdo->prepare($catSql);
                $stmt->execute($params);
                $byCategory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 4. Breakdown per Regional RSM Area
                $rsmSql = "
                    SELECT 
                        rsm_area,
                        COUNT(*) as submissions,
                        COUNT(DISTINCT store_name) as stores,
                        COUNT(DISTINCT machine_no) as machines,
                        ROUND(AVG(tinta_ok) * 100, 1) as avg_tinta,
                        ROUND(AVG(pembersihan_all_ok) * 100, 1) as avg_clean
                    FROM dm_raw
                    WHERE $whereSql
                    GROUP BY rsm_area
                    ORDER BY submissions DESC
                ";
                $stmt = $pdo->prepare($rsmSql);
                $stmt->execute($params);
                $byRegion = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 5. Store Matrix Paginated
                $storeCountSql = "
                    SELECT COUNT(*) FROM (
                        SELECT store_name, machine_no FROM dm_raw WHERE $whereSql GROUP BY store_name, machine_no
                    )
                ";
                $stmt = $pdo->prepare($storeCountSql);
                $stmt->execute($params);
                $totalStoreMatrix = (int)$stmt->fetchColumn();

                $storeOffset = ($storePage - 1) * $perPage;
                $storeMatrixSql = "
                    SELECT 
                        store_name, sap_code, category, rsm_area, area,
                        machine_type, machine_no,
                        COUNT(*) as total_checks,
                        MAX(tanggal_report) as last_date,
                        SUM(tinta_ok) as tinta_ok_cnt,
                        SUM(pembersihan_all_ok) as clean_ok_cnt,
                        ROUND(AVG(tinta_ok) * 100, 1) as compliance_pct
                    FROM dm_raw
                    WHERE $whereSql
                    GROUP BY store_name, machine_no
                    ORDER BY total_checks DESC, store_name ASC
                    LIMIT $perPage OFFSET $storeOffset
                ";
                $stmt = $pdo->prepare($storeMatrixSql);
                $stmt->execute($params);
                $storeRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 6. Raw Submissions Paginated
                $rawCountSql = "SELECT COUNT(*) FROM dm_raw WHERE $whereSql";
                $stmt = $pdo->prepare($rawCountSql);
                $stmt->execute($params);
                $totalRaw = (int)$stmt->fetchColumn();

                $rawOffset = ($rawPage - 1) * $perPage;
                $rawSql = "
                    SELECT 
                        year, month, submission_date, tanggal_report, store_name, sap_code, category, rsm_area, area,
                        tl_name, machine_type, machine_no, dc_name, kesimpulan,
                        tinta_ok, d200_nozzle_ok, discovery_brush_ok, manual_nozzle_ok,
                        mix2win_steps_ok, pembersihan_all_ok
                    FROM dm_raw
                    WHERE $whereSql
                    ORDER BY tanggal_report DESC, id DESC
                    LIMIT $perPage OFFSET $rawOffset
                ";
                $stmt = $pdo->prepare($rawSql);
                $stmt->execute($params);
                $rawRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                return [
                    'months' => $activeMonths,
                    'kpis' => $kpis,
                    'by_machine_type' => $byMachine,
                    'by_category' => $byCategory,
                    'by_region' => $byRegion,
                    'store_matrix' => [
                        'rows' => $storeRows,
                        'total_rows' => $totalStoreMatrix,
                        'total' => $totalStoreMatrix,
                        'page' => $storePage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalStoreMatrix / $perPage),
                        'from' => $totalStoreMatrix > 0 ? ($storeOffset + 1) : 0,
                        'to' => min($storeOffset + $perPage, $totalStoreMatrix),
                    ],
                    'submissions' => [
                        'rows' => $rawRows,
                        'total' => $totalRaw,
                        'page' => $rawPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalRaw / $perPage),
                        'from' => $totalRaw > 0 ? ($rawOffset + 1) : 0,
                        'to' => min($rawOffset + $perPage, $totalRaw),
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Daily Maintenance Dashboard: " . $e->getMessage());
                return [
                    'months' => $activeMonths,
                    'kpis' => ['total_submissions' => 0, 'total_stores' => 0, 'total_machines' => 0, 'tinta_rate' => 0, 'nozzle_rate' => 0, 'mix2win_rate' => 0, 'pembersihan_rate' => 0],
                    'by_machine_type' => [],
                    'by_category' => [],
                    'by_region' => [],
                    'store_matrix' => ['rows' => [], 'total_rows' => 0, 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
                ];
            }
        });
    }

    /**
     * Hitung Data Dashboard Customer Database Dulux
     * Langsung dari SQLite db terindeks storage/app/dulux_data/customer_db.sqlite
     */
    private function calculateCustomerDbDashboardData(
        $template,
        $startMonth,
        $startYear,
        $endMonth,
        $endYear,
        $selectedRegion = null,
        $selectedAreaId = null,
        $selectedLocationId = null,
        $selectedCustomerType = null,
        $selectedBrand = null,
        $selectedReason = null,
        $search = null,
        $topStorePage = 1,
        $rawPage = 1,
        $perPage = 50
    ) {
        $sqlitePath = storage_path('app/dulux_data/customer_db.sqlite');
        $gzPath     = storage_path('app/dulux_data/customer_db.sqlite.gz');

        if (!file_exists($sqlitePath) || filesize($sqlitePath) < 500000 || (file_exists($gzPath) && filemtime($gzPath) > filemtime($sqlitePath))) {
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
                    \Log::error("Auto-extraction of customer_db.sqlite.gz failed: " . $e->getMessage());
                }
            }
        }

        if (!file_exists($sqlitePath)) {
            return [];
        }

        $sMonth = max(1, min(12, (int)$startMonth));
        $eMonth = max(1, min(12, (int)$endMonth));
        $sYear  = (int)($startYear ?: 2025);
        $eYear  = (int)($endYear ?: 2026);

        $selectedAreaName = $selectedAreaId ? (is_numeric($selectedAreaId) ? Branch::where('id', $selectedAreaId)->value('name') : $selectedAreaId) : null;
        $selectedStoreName = $selectedLocationId ? (is_numeric($selectedLocationId) ? WorkLocation::where('id', $selectedLocationId)->value('name') : $selectedLocationId) : null;

        $cacheKey = 'cust_db_v1_' . md5($template->id . "_{$sYear}_{$sMonth}_{$eYear}_{$eMonth}_{$selectedRegion}_{$selectedAreaName}_{$selectedStoreName}_{$selectedCustomerType}_{$selectedBrand}_{$selectedReason}_{$search}_{$topStorePage}_{$rawPage}_{$perPage}");

        return Cache::remember($cacheKey, 300, function() use ($sqlitePath, $sYear, $sMonth, $eYear, $eMonth, $selectedRegion, $selectedAreaName, $selectedStoreName, $selectedCustomerType, $selectedBrand, $selectedReason, $search, $topStorePage, $rawPage, $perPage) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $where = [];
                $params = [];

                if ($sYear === $eYear) {
                    $where[] = "year = ?";
                    $params[] = $sYear;
                    $where[] = "month >= ? AND month <= ?";
                    $params[] = $sMonth;
                    $params[] = $eMonth;
                } else {
                    $where[] = "((year = ? AND month >= ?) OR (year = ? AND month <= ?) OR (year > ? AND year < ?))";
                    $params[] = $sYear; $params[] = $sMonth;
                    $params[] = $eYear; $params[] = $eMonth;
                    $params[] = $sYear; $params[] = $eYear;
                }

                if ($selectedRegion) {
                    $rsmVariants = $this->getRsmQueryVariants($selectedRegion);
                    $inPlaceholders = implode(',', array_fill(0, count($rsmVariants), '?'));
                    $where[] = "rsm_area IN ($inPlaceholders)";
                    foreach ($rsmVariants as $rv) {
                        $params[] = $rv;
                    }
                }
                if ($selectedAreaName) {
                    $where[] = "area = ?";
                    $params[] = $selectedAreaName;
                }
                if ($selectedStoreName) {
                    $where[] = "store_name = ?";
                    $params[] = $selectedStoreName;
                }
                if ($selectedCustomerType) {
                    $where[] = "tipe_pelanggan = ?";
                    $params[] = $selectedCustomerType;
                }
                if ($selectedBrand) {
                    $where[] = "(brand_dicari LIKE ? OR brand_dibeli LIKE ?)";
                    $params[] = "%{$selectedBrand}%";
                    $params[] = "%{$selectedBrand}%";
                }
                if ($selectedReason) {
                    $where[] = "alasan = ?";
                    $params[] = $selectedReason;
                }
                if ($search) {
                    $where[] = "(nama_pelanggan LIKE ? OR no_hp LIKE ? OR store_name LIKE ? OR sap_code LIKE ? OR alamat LIKE ? OR nama_dc LIKE ?)";
                    $like = "%{$search}%";
                    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
                }

                $whereSql = !empty($where) ? implode(' AND ', $where) : "1=1";

                // 1. Executive KPIs
                $kpiSql = "
                    SELECT 
                        COUNT(*) as total_records,
                        COALESCE(SUM(value_pembelian), 0) as total_value,
                        COALESCE(AVG(value_pembelian), 0) as avg_basket_size,
                        COUNT(DISTINCT store_name) as unique_stores,
                        COUNT(DISTINCT nama_dc) as unique_dcs,
                        COALESCE(SUM(is_switched), 0) as switched_cnt,
                        COALESCE(SUM(is_dulux_bought), 0) as dulux_bought_cnt
                    FROM cust_raw
                    WHERE $whereSql
                ";
                $stmt = $pdo->prepare($kpiSql);
                $stmt->execute($params);
                $kpis = $stmt->fetch(\PDO::FETCH_ASSOC);

                $tot = (int)$kpis['total_records'];
                $kpis['switched_pct'] = $tot > 0 ? round(((int)$kpis['switched_cnt'] / $tot) * 100, 1) : 0;
                $kpis['dulux_bought_pct'] = $tot > 0 ? round(((int)$kpis['dulux_bought_cnt'] / $tot) * 100, 1) : 0;

                // 2. Consumer Insights: Segmen Tipe Pelanggan
                $typeSql = "
                    SELECT 
                        tipe_pelanggan,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct,
                        COALESCE(SUM(value_pembelian), 0) as total_val,
                        COALESCE(AVG(value_pembelian), 0) as avg_val
                    FROM cust_raw
                    WHERE $whereSql
                    GROUP BY tipe_pelanggan
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($typeSql);
                $stmt->execute($params);
                $customerTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 3. Consumer Insights: Alasan Memilih Brand
                $reasonSql = "
                    SELECT 
                        alasan,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND alasan IS NOT NULL AND alasan != ''
                    GROUP BY alasan
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($reasonSql);
                $stmt->execute($params);
                $reasons = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 4. Consumer Insights: Tujuan Datang ke Toko
                $purposeSql = "
                    SELECT 
                        tujuan_ke_toko,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND tujuan_ke_toko IS NOT NULL AND tujuan_ke_toko != ''
                    GROUP BY tujuan_ke_toko
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($purposeSql);
                $stmt->execute($params);
                $purposes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 5. Consumer Insights: Top Brand Dicari vs Brand Dibeli
                $soughtSql = "
                    SELECT 
                        brand_dicari,
                        COUNT(*) as cnt,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND brand_dicari IS NOT NULL AND brand_dicari != ''
                    GROUP BY brand_dicari
                    ORDER BY cnt DESC
                    LIMIT 8
                ";
                $stmt = $pdo->prepare($soughtSql);
                $stmt->execute($params);
                $brandsSought = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $boughtSql = "
                    SELECT 
                        brand_dibeli,
                        COUNT(*) as cnt,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND brand_dibeli IS NOT NULL AND brand_dibeli != ''
                    GROUP BY brand_dibeli
                    ORDER BY cnt DESC
                    LIMIT 8
                ";
                $stmt = $pdo->prepare($boughtSql);
                $stmt->execute($params);
                $brandsBought = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 6. Consumer Insights: Tipe Pengecatan
                $paintSql = "
                    SELECT 
                        tipe_pengecatan,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND tipe_pengecatan IS NOT NULL AND tipe_pengecatan != ''
                    GROUP BY tipe_pengecatan
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($paintSql);
                $stmt->execute($params);
                $paintTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 7. Consumer Insights: Kebutuhan Preview Warna Visualizer
                $previewSql = "
                    SELECT 
                        memerlukan_preview,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql AND memerlukan_preview IS NOT NULL AND memerlukan_preview != ''
                    GROUP BY memerlukan_preview
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($previewSql);
                $stmt->execute($params);
                $previewNeeds = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 8. Consumer Insights: Minat Program Mitra Dulux
                $painterSql = "
                    SELECT 
                        (CASE WHEN painter_info LIKE '%CHECKED: Saya bersedia%' THEN 'Bersedia / Tertarik Program Mitra'
                              WHEN painter_info LIKE '%UNCHECKED%' THEN 'Belum Bersedia'
                              ELSE 'Tidak Mengisi / Bukan Tukang' END) as status_painter,
                        COUNT(*) as total_count,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct
                    FROM cust_raw
                    WHERE $whereSql
                    GROUP BY status_painter
                    ORDER BY total_count DESC
                ";
                $stmt = $pdo->prepare($painterSql);
                $stmt->execute($params);
                $painterLoyalty = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 9. Regional Ranking Breakdown
                $rsmSql = "
                    SELECT 
                        rsm_area,
                        COUNT(*) as total_count,
                        COUNT(DISTINCT store_name) as stores,
                        COUNT(DISTINCT nama_dc) as dcs,
                        COALESCE(SUM(value_pembelian), 0) as total_val,
                        COALESCE(AVG(value_pembelian), 0) as avg_val,
                        ROUND(COUNT(*) * 100.0 / " . max(1, $tot) . ", 1) as pct,
                        COALESCE(SUM(is_switched), 0) as switched_cnt
                    FROM cust_raw
                    WHERE $whereSql
                    GROUP BY rsm_area
                    ORDER BY total_val DESC
                ";
                $stmt = $pdo->prepare($rsmSql);
                $stmt->execute($params);
                $byRegion = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 10. Top Stores Paginated
                $storeCountSql = "
                    SELECT COUNT(*) FROM (
                        SELECT store_name FROM cust_raw WHERE $whereSql GROUP BY store_name
                    )
                ";
                $stmt = $pdo->prepare($storeCountSql);
                $stmt->execute($params);
                $totalStores = (int)$stmt->fetchColumn();

                $topStoreOffset = ($topStorePage - 1) * $perPage;
                $topStoreSql = "
                    SELECT 
                        store_name, sap_code, rsm_area, area,
                        COUNT(*) as total_customers,
                        COALESCE(SUM(value_pembelian), 0) as total_val,
                        COALESCE(AVG(value_pembelian), 0) as avg_val,
                        COALESCE(SUM(is_switched), 0) as switched_cnt,
                        COUNT(DISTINCT nama_dc) as total_dcs
                    FROM cust_raw
                    WHERE $whereSql
                    GROUP BY store_name
                    ORDER BY total_val DESC, total_customers DESC
                    LIMIT $perPage OFFSET $topStoreOffset
                ";
                $stmt = $pdo->prepare($topStoreSql);
                $stmt->execute($params);
                $storeRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 11. Top DCs Ranking
                $topDcSql = "
                    SELECT 
                        nama_dc,
                        MIN(store_name) as store_name,
                        MIN(rsm_area) as rsm_area,
                        COUNT(*) as total_customers,
                        COALESCE(SUM(value_pembelian), 0) as total_val,
                        COALESCE(SUM(is_switched), 0) as switched_cnt
                    FROM cust_raw
                    WHERE $whereSql AND nama_dc IS NOT NULL AND nama_dc != ''
                    GROUP BY nama_dc
                    ORDER BY total_customers DESC
                    LIMIT 20
                ";
                $stmt = $pdo->prepare($topDcSql);
                $stmt->execute($params);
                $topDcs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 12. Raw Submissions Paginated
                $rawCountSql = "SELECT COUNT(*) FROM cust_raw WHERE $whereSql";
                $stmt = $pdo->prepare($rawCountSql);
                $stmt->execute($params);
                $totalRaw = (int)$stmt->fetchColumn();

                $rawOffset = ($rawPage - 1) * $perPage;
                $rawSql = "
                    SELECT 
                        id, year, month, submission_date, tanggal, store_name, sap_code, sap_gab,
                        rsm_area, area, nama_pelanggan, alamat, no_hp,
                        tipe_pelanggan, painter_info, tujuan_ke_toko, brand_dicari, brand_dibeli,
                        alasan, tipe_pengecatan, memerlukan_preview, value_pembelian,
                        is_switched, is_dulux_bought, nama_dc, keterangan, foto_1, foto_2, foto_3
                    FROM cust_raw
                    WHERE $whereSql
                    ORDER BY year DESC, id DESC
                    LIMIT $perPage OFFSET $rawOffset
                ";
                $stmt = $pdo->prepare($rawSql);
                $stmt->execute($params);
                $rawRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                return [
                    'kpis' => $kpis,
                    'insights' => [
                        'customer_types' => $customerTypes,
                        'reasons' => $reasons,
                        'purposes' => $purposes,
                        'brands_sought' => $brandsSought,
                        'brands_bought' => $brandsBought,
                        'paint_types' => $paintTypes,
                        'preview_needs' => $previewNeeds,
                        'painter_loyalty' => $painterLoyalty,
                    ],
                    'by_region' => $byRegion,
                    'top_stores' => [
                        'rows' => $storeRows,
                        'total' => $totalStores,
                        'page' => $topStorePage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalStores / $perPage),
                        'from' => $totalStores > 0 ? ($topStoreOffset + 1) : 0,
                        'to' => min($topStoreOffset + $perPage, $totalStores),
                    ],
                    'top_dcs' => $topDcs,
                    'submissions' => [
                        'rows' => $rawRows,
                        'total' => $totalRaw,
                        'page' => $rawPage,
                        'per_page' => $perPage,
                        'total_pages' => (int)ceil($totalRaw / $perPage),
                        'from' => $totalRaw > 0 ? ($rawOffset + 1) : 0,
                        'to' => min($rawOffset + $perPage, $totalRaw),
                    ]
                ];
            } catch (\Throwable $e) {
                \Log::error("Failed to calculate Customer Database Dashboard: " . $e->getMessage());
                return [
                    'kpis' => ['total_records' => 0, 'total_value' => 0, 'avg_basket_size' => 0, 'unique_stores' => 0, 'unique_dcs' => 0, 'switched_cnt' => 0, 'dulux_bought_cnt' => 0, 'switched_pct' => 0, 'dulux_bought_pct' => 0],
                    'insights' => [
                        'customer_types' => [], 'reasons' => [], 'purposes' => [], 'brands_sought' => [],
                        'brands_bought' => [], 'paint_types' => [], 'preview_needs' => [], 'painter_loyalty' => [],
                    ],
                    'by_region' => [],
                    'top_stores' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0],
                    'top_dcs' => [],
                    'submissions' => ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0, 'from' => 0, 'to' => 0]
                ];
            }
        });
    }

    /**
     * =========================================================================
     * REPORT TEMPLATES / FORM BUILDER MANAGEMENT FOR PRINCIPAL PORTAL
     * =========================================================================
     */

    /**
     * Report Templates List for Principal (Form Builder)
     */
    public function reportTemplatesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $search = $request->query('q');
        $category = $request->query('category');
        $status = $request->query('status'); // 'all', 'active', 'inactive'

        $query = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->with(['fields', 'principals', 'products', 'positions', 'employees'])
            ->withCount('submissions');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('report_templates.title', 'LIKE', "%{$search}%")
                  ->orWhere('report_templates.code', 'LIKE', "%{$search}%")
                  ->orWhere('report_templates.description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($category)) {
            $query->where('report_templates.category', $category);
        }

        if ($status === 'active') {
            $query->where('report_templates.is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('report_templates.is_active', false);
        }

        $templates = $query->orderByRaw("
            CASE 
                WHEN report_templates.code LIKE '%OFFTAKE%' OR report_templates.title LIKE '%offtake%' OR report_templates.category = 'offtake' THEN 1
                WHEN report_templates.code LIKE '%STOCK-END%' OR report_templates.code LIKE '%STOK-END%' OR report_templates.title LIKE '%stock end%' OR report_templates.title LIKE '%stok end%' THEN 2
                WHEN report_templates.code LIKE '%OOS%' OR report_templates.title LIKE '%oos%' OR report_templates.title LIKE '%out of stock%' THEN 3
                WHEN report_templates.code LIKE '%CBP%' OR report_templates.code LIKE '%PRICING%' OR report_templates.title LIKE '%cbp%' OR report_templates.title LIKE '%pricing%' OR report_templates.category IN ('pricing', 'price') THEN 4
                WHEN report_templates.code LIKE '%DAILY-MAINTENANCE%' OR report_templates.code LIKE '%MAINTENANCE%' OR report_templates.title LIKE '%maintenance%' OR report_templates.title LIKE '%maintance%' THEN 5
                WHEN report_templates.code LIKE '%DATABASE-PELANGGAN%' OR report_templates.code LIKE '%DATA-PELANGGAN%' OR report_templates.title LIKE '%pelanggan%' OR report_templates.title LIKE '%konsumen%' THEN 6
                WHEN report_templates.code LIKE '%TRAFIK%' OR report_templates.title LIKE '%trafik%' THEN 7
                WHEN report_templates.code LIKE '%MITRA%' OR report_templates.title LIKE '%mitra%' THEN 8
                ELSE 10
            END ASC, report_templates.id ASC
        ")->paginate(15);

        // Stats
        $baseStatsQuery = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal);
        $totalTemplates = (clone $baseStatsQuery)->count();
        $totalActive = (clone $baseStatsQuery)->where('report_templates.is_active', true)->count();
        $totalFields = \App\Models\ReportFormField::whereIn('report_template_id', (clone $baseStatsQuery)->pluck('report_templates.id'))->count();

        $categories = [
            'offtake' => 'Offtake / Penjualan Harian',
            'sellout' => 'Sell-Out (SPG / MD / Event)',
            'stock' => 'Cek Stok & OOS (Barang Kosong)',
            'pricing' => 'Cek Harga & Price Tag Tracking',
            'price' => 'Price Monitoring (Harga & Kompetitor)',
            'promo' => 'Tracking Program Promo',
            'display' => 'Display & Sewa Display (Rent/Add Display)',
            'posm' => 'POSM & Material Promosi / Stiker',
            'competitor' => 'Market Share & Aktivitas Kompetitor',
            'expiry' => 'Monitoring Expired Date (Kadaluarsa)',
            'survey' => 'Survey Pasar / Profil Toko',
            'general' => 'Pelaporan Umum / Kunjungan Biasa',
        ];

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.report_templates.index', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'templates',
            'totalTemplates',
            'totalActive',
            'totalFields',
            'categories',
            'search',
            'category',
            'status',
            'setting'
        ));
    }

    /**
     * Show form to create a new report template
     */
    public function createReportTemplate(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $template = new ReportTemplate([
            'is_active' => true,
            'require_gps' => true,
            'require_signature' => false,
            'schedule_type' => 'daily',
            'target_count' => 1,
            'category' => 'offtake',
        ]);

        $products = Product::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->orderBy('name')->get();
        $positions = Position::where('is_active', true)->orderBy('name')->get();
        if ($positions->isEmpty()) {
            $positions = Position::orderBy('name')->get();
        }
        $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->where('status', 'active')->orderBy('full_name')->get();
        if ($employees->isEmpty()) {
            $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->orderBy('full_name')->get();
        }
        $workLocations = WorkLocation::where('is_active', true)->orderBy('name')->get();

        $categories = [
            'offtake' => 'Offtake / Penjualan Harian',
            'sellout' => 'Sell-Out (SPG / MD / Event)',
            'stock' => 'Cek Stok & OOS (Barang Kosong)',
            'pricing' => 'Cek Harga & Price Tag Tracking',
            'price' => 'Price Monitoring (Harga & Kompetitor)',
            'promo' => 'Tracking Program Promo',
            'display' => 'Display & Sewa Display (Rent/Add Display)',
            'posm' => 'POSM & Material Promosi / Stiker',
            'competitor' => 'Market Share & Aktivitas Kompetitor',
            'expiry' => 'Monitoring Expired Date (Kadaluarsa)',
            'survey' => 'Survey Pasar / Profil Toko',
            'general' => 'Pelaporan Umum / Kunjungan Biasa',
        ];

        $fieldTypes = $this->getAvailableFieldTypes();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();
        $isEdit = false;

        return view('portal.report_templates.form', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'template',
            'products',
            'positions',
            'employees',
            'workLocations',
            'categories',
            'fieldTypes',
            'isEdit',
            'setting'
        ));
    }

    /**
     * Store a newly created report template
     */
    public function storeReportTemplate(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'schedule_type' => 'required|in:daily,weekly,monthly',
            'target_count' => 'nullable|integer|min:1',
            'report_days' => 'nullable|array',
            'require_gps' => 'nullable|boolean',
            'require_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'products' => 'nullable|array',
            'products.*' => 'integer',
            'positions' => 'nullable|array',
            'positions.*' => 'integer',
            'employees' => 'nullable|array',
            'employees.*' => 'integer',
            'fields' => 'nullable|array',
            'assignments' => 'nullable|array',
        ]);

        $code = strtoupper(trim($validated['code']));
        // Check uniqueness within principal scope or globally
        $existing = ReportTemplate::where('code', $code)->first();
        if ($existing) {
            $code = $code . '-' . time();
        }

        $template = ReportTemplate::create([
            'principal_id' => $tenantPrincipal->id,
            'title' => trim($validated['title']),
            'code' => $code,
            'category' => $validated['category'],
            'schedule_type' => $validated['schedule_type'],
            'target_count' => $validated['target_count'] ?? 1,
            'report_days' => $request->input('report_days', []),
            'require_gps' => $request->boolean('require_gps', true),
            'require_signature' => $request->boolean('require_signature', false),
            'is_active' => $request->boolean('is_active', true),
            'description' => $validated['description'] ?? null,
            'version' => 1,
        ]);

        // Sync pivot principals
        $template->principals()->sync($scopedPrincipalIds);

        // Sync products
        if (!empty($validated['products'])) {
            $template->products()->sync($validated['products']);
        }

        // Sync positions
        if (!empty($validated['positions'])) {
            $template->positions()->sync($validated['positions']);
        }

        // Sync employees
        if (!empty($validated['employees'])) {
            $template->employees()->sync($validated['employees']);
        }

        // Create Fields
        $fieldsInput = $request->input('fields', []);
        if (is_array($fieldsInput)) {
            $order = 0;
            foreach ($fieldsInput as $f) {
                if (empty($f['field_label'])) continue;

                $fieldName = !empty($f['field_name']) 
                    ? \Illuminate\Support\Str::snake(trim($f['field_name'])) 
                    : \Illuminate\Support\Str::snake(\Illuminate\Support\Str::slug(trim($f['field_label'])));

                $options = null;
                if (!empty($f['options'])) {
                    if (is_array($f['options'])) {
                        $options = array_values(array_filter(array_map('trim', $f['options'])));
                    } elseif (is_string($f['options'])) {
                        $options = array_values(array_filter(array_map('trim', explode(',', $f['options']))));
                    }
                }

                \App\Models\ReportFormField::create([
                    'report_template_id' => $template->id,
                    'field_name' => $fieldName,
                    'field_label' => trim($f['field_label']),
                    'field_type' => $f['field_type'] ?? 'text',
                    'placeholder' => !empty($f['placeholder']) ? trim($f['placeholder']) : null,
                    'help_text' => !empty($f['help_text']) ? trim($f['help_text']) : null,
                    'options' => $options,
                    'is_required' => !empty($f['is_required']),
                    'is_readonly' => !empty($f['is_readonly']),
                    'order_index' => $order++,
                ]);
            }
        }

        // Create Assignments
        $assignmentsInput = $request->input('assignments', []);
        if (is_array($assignmentsInput)) {
            foreach ($assignmentsInput as $a) {
                if (empty($a['employee_id']) && empty($a['position_id']) && empty($a['work_location_id']) && empty($a['channel'])) {
                    continue;
                }
                \App\Models\ReportTemplateAssignment::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $tenantPrincipal->id,
                    'employee_id' => !empty($a['employee_id']) ? (int)$a['employee_id'] : null,
                    'position_id' => !empty($a['position_id']) ? (int)$a['position_id'] : null,
                    'work_location_id' => !empty($a['work_location_id']) ? (int)$a['work_location_id'] : null,
                    'channel' => !empty($a['channel']) ? trim($a['channel']) : null,
                ]);
            }
        }

        return redirect()->route('portal.report_templates', ['p' => $tenantPrincipal->id])
            ->with('success', "Form Template '{$template->title}' berhasil dibuat!");
    }

    /**
     * Show form to edit an existing report template
     */
    public function editReportTemplate(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->with(['fields', 'principals', 'products', 'positions', 'employees', 'assignments'])
            ->findOrFail($id);

        $activeTemplates = $this->getActiveTemplates($scopedPrincipalIds, $tenantPrincipal);

        $products = Product::whereIn('principal_id', $scopedPrincipalIds)->where('is_active', true)->orderBy('name')->get();
        $positions = Position::where('is_active', true)->orderBy('name')->get();
        if ($positions->isEmpty()) {
            $positions = Position::orderBy('name')->get();
        }
        $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->where('status', 'active')->orderBy('full_name')->get();
        if ($employees->isEmpty()) {
            $employees = Employee::whereIn('principal_id', $scopedPrincipalIds)->orderBy('full_name')->get();
        }
        $workLocations = WorkLocation::where('is_active', true)->orderBy('name')->get();

        $categories = [
            'offtake' => 'Offtake / Penjualan Harian',
            'sellout' => 'Sell-Out (SPG / MD / Event)',
            'stock' => 'Cek Stok & OOS (Barang Kosong)',
            'pricing' => 'Cek Harga & Price Tag Tracking',
            'price' => 'Price Monitoring (Harga & Kompetitor)',
            'promo' => 'Tracking Program Promo',
            'display' => 'Display & Sewa Display (Rent/Add Display)',
            'posm' => 'POSM & Material Promosi / Stiker',
            'competitor' => 'Market Share & Aktivitas Kompetitor',
            'expiry' => 'Monitoring Expired Date (Kadaluarsa)',
            'survey' => 'Survey Pasar / Profil Toko',
            'general' => 'Pelaporan Umum / Kunjungan Biasa',
        ];

        $fieldTypes = $this->getAvailableFieldTypes();
        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();
        $isEdit = true;

        return view('portal.report_templates.form', compact(
            'tenantPrincipal',
            'tenantPrincipalsAll',
            'brandColor',
            'activeTemplates',
            'template',
            'products',
            'positions',
            'employees',
            'workLocations',
            'categories',
            'fieldTypes',
            'isEdit',
            'setting'
        ));
    }

    /**
     * Update an existing report template
     */
    public function updateReportTemplate(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'schedule_type' => 'required|in:daily,weekly,monthly',
            'target_count' => 'nullable|integer|min:1',
            'report_days' => 'nullable|array',
            'require_gps' => 'nullable|boolean',
            'require_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'products' => 'nullable|array',
            'products.*' => 'integer',
            'positions' => 'nullable|array',
            'positions.*' => 'integer',
            'employees' => 'nullable|array',
            'employees.*' => 'integer',
            'fields' => 'nullable|array',
            'assignments' => 'nullable|array',
        ]);

        $template->update([
            'title' => trim($validated['title']),
            'code' => strtoupper(trim($validated['code'])),
            'category' => $validated['category'],
            'schedule_type' => $validated['schedule_type'],
            'target_count' => $validated['target_count'] ?? 1,
            'report_days' => $request->input('report_days', []),
            'require_gps' => $request->boolean('require_gps', true),
            'require_signature' => $request->boolean('require_signature', false),
            'is_active' => $request->boolean('is_active', true),
            'description' => $validated['description'] ?? null,
            'version' => ($template->version ?? 1) + 1,
        ]);

        // Sync pivot principals if not linked
        if (!$template->principals()->exists()) {
            $template->principals()->sync($scopedPrincipalIds);
        }

        // Sync products
        $template->products()->sync($validated['products'] ?? []);

        // Sync positions
        $template->positions()->sync($validated['positions'] ?? []);

        // Sync employees
        $template->employees()->sync($validated['employees'] ?? []);

        // Re-sync Form Fields cleanly
        $fieldsInput = $request->input('fields', []);
        $template->fields()->delete();
        if (is_array($fieldsInput)) {
            $order = 0;
            foreach ($fieldsInput as $f) {
                if (empty($f['field_label'])) continue;

                $fieldName = !empty($f['field_name']) 
                    ? \Illuminate\Support\Str::snake(trim($f['field_name'])) 
                    : \Illuminate\Support\Str::snake(\Illuminate\Support\Str::slug(trim($f['field_label'])));

                $options = null;
                if (!empty($f['options'])) {
                    if (is_array($f['options'])) {
                        $options = array_values(array_filter(array_map('trim', $f['options'])));
                    } elseif (is_string($f['options'])) {
                        $options = array_values(array_filter(array_map('trim', explode(',', $f['options']))));
                    }
                }

                \App\Models\ReportFormField::create([
                    'report_template_id' => $template->id,
                    'field_name' => $fieldName,
                    'field_label' => trim($f['field_label']),
                    'field_type' => $f['field_type'] ?? 'text',
                    'placeholder' => !empty($f['placeholder']) ? trim($f['placeholder']) : null,
                    'help_text' => !empty($f['help_text']) ? trim($f['help_text']) : null,
                    'options' => $options,
                    'is_required' => !empty($f['is_required']),
                    'is_readonly' => !empty($f['is_readonly']),
                    'order_index' => $order++,
                ]);
            }
        }

        // Re-sync Assignments
        $assignmentsInput = $request->input('assignments', []);
        $template->assignments()->delete();
        if (is_array($assignmentsInput)) {
            foreach ($assignmentsInput as $a) {
                if (empty($a['employee_id']) && empty($a['position_id']) && empty($a['work_location_id']) && empty($a['channel'])) {
                    continue;
                }
                \App\Models\ReportTemplateAssignment::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $tenantPrincipal->id,
                    'employee_id' => !empty($a['employee_id']) ? (int)$a['employee_id'] : null,
                    'position_id' => !empty($a['position_id']) ? (int)$a['position_id'] : null,
                    'work_location_id' => !empty($a['work_location_id']) ? (int)$a['work_location_id'] : null,
                    'channel' => !empty($a['channel']) ? trim($a['channel']) : null,
                ]);
            }
        }

        if ($request->has('save_and_continue')) {
            return redirect()->route('portal.report_templates.edit', ['id' => $template->id, 'p' => $tenantPrincipal->id])
                ->with('success', "Form Template '{$template->title}' berhasil diperbarui!");
        }

        return redirect()->route('portal.report_templates', ['p' => $tenantPrincipal->id])
            ->with('success', "Perubahan Form Template '{$template->title}' berhasil disimpan!");
    }

    /**
     * Delete a report template
     */
    public function destroyReportTemplate(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)->findOrFail($id);
        $title = $template->title;

        $template->fields()->delete();
        $template->assignments()->delete();
        $template->principals()->detach();
        $template->products()->detach();
        $template->positions()->detach();
        $template->employees()->detach();
        $template->delete();

        return redirect()->route('portal.report_templates', ['p' => $tenantPrincipal->id])
            ->with('success', "Form Template '{$title}' berhasil dihapus!");
    }

    /**
     * Quick toggle is_active status of template
     */
    public function toggleReportTemplateActive(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $template = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)->findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $template->is_active,
                'message' => "Status form '{$template->title}' berhasil " . ($template->is_active ? 'diaktifkan' : 'dinonaktifkan') . "!"
            ]);
        }

        return redirect()->back()->with('success', "Status form '{$template->title}' berhasil diubah!");
    }

    /**
     * Duplicate a report template
     */
    public function duplicateReportTemplate(Request $request, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $original = $this->getTemplateBaseQuery($scopedPrincipalIds, $tenantPrincipal)
            ->with(['fields', 'assignments', 'products', 'positions', 'employees'])
            ->findOrFail($id);

        $newCode = $original->code . '-COPY-' . strtoupper(\Illuminate\Support\Str::random(4));
        $newTitle = $original->title . ' (Duplikat)';

        $clone = ReportTemplate::create([
            'principal_id' => $original->principal_id ?? $tenantPrincipal->id,
            'title' => $newTitle,
            'code' => $newCode,
            'category' => $original->category,
            'schedule_type' => $original->schedule_type,
            'target_count' => $original->target_count,
            'report_days' => $original->report_days,
            'require_gps' => $original->require_gps,
            'require_signature' => $original->require_signature,
            'is_active' => false,
            'description' => $original->description,
            'dashboard_config' => $original->dashboard_config,
            'version' => 1,
        ]);

        $clone->principals()->sync($scopedPrincipalIds);
        $clone->products()->sync($original->products->pluck('id'));
        $clone->positions()->sync($original->positions->pluck('id'));
        $clone->employees()->sync($original->employees->pluck('id'));

        foreach ($original->fields as $f) {
            \App\Models\ReportFormField::create([
                'report_template_id' => $clone->id,
                'field_name' => $f->field_name,
                'field_label' => $f->field_label,
                'field_type' => $f->field_type,
                'placeholder' => $f->placeholder,
                'help_text' => $f->help_text,
                'options' => $f->options,
                'validation_rules' => $f->validation_rules,
                'is_required' => $f->is_required,
                'is_readonly' => $f->is_readonly,
                'order_index' => $f->order_index,
            ]);
        }

        foreach ($original->assignments as $a) {
            \App\Models\ReportTemplateAssignment::create([
                'report_template_id' => $clone->id,
                'principal_id' => $a->principal_id,
                'employee_id' => $a->employee_id,
                'position_id' => $a->position_id,
                'work_location_id' => $a->work_location_id,
                'channel' => $a->channel,
            ]);
        }

        return redirect()->route('portal.report_templates.edit', ['id' => $clone->id, 'p' => $tenantPrincipal->id])
            ->with('success', "Form Template berhasil diduplikasi menjadi '{$newTitle}'. Anda sekarang dapat mengeditnya!");
    }

    /**
     * Helper list of field types
     */
    protected function getAvailableFieldTypes(): array
    {
        return [
            'product_select' => ['label' => 'Pilihan Produk Tertentu (Dari Master SKU)', 'icon' => 'fa-boxes-stacked', 'has_options' => true, 'hint' => 'Otomatis menarik daftar produk SKU sesuai konfigurasi template'],
            'text' => ['label' => 'Teks Singkat', 'icon' => 'fa-font', 'has_options' => false, 'hint' => 'Input satu baris teks biasa'],
            'textarea' => ['label' => 'Paragraf / Catatan Panjang', 'icon' => 'fa-align-left', 'has_options' => false, 'hint' => 'Kotak teks multi-baris untuk ulasan, catatan, atau deskripsi'],
            'number' => ['label' => 'Angka / Kuantitas (Qty)', 'icon' => 'fa-arrow-up-1-9', 'has_options' => false, 'hint' => 'Input numerik murni (misal: stok, jumlah display, kuantitas)'],
            'currency' => ['label' => 'Nilai Rupiah (IDR)', 'icon' => 'fa-money-bill-wave', 'has_options' => false, 'hint' => 'Input nominal harga atau total rupiah'],
            'dropdown' => ['label' => 'Dropdown Pilihan Tunggal', 'icon' => 'fa-square-caret-down', 'has_options' => true, 'hint' => 'Menu drop-down untuk memilih salah satu opsi'],
            'radio' => ['label' => 'Radio Button (Pilihan Ganda)', 'icon' => 'fa-circle-dot', 'has_options' => true, 'hint' => 'Pilihan satu opsi dengan tampilan tombol bulat'],
            'checkbox' => ['label' => 'Checkbox (Multi-Pilihan)', 'icon' => 'fa-square-check', 'has_options' => true, 'hint' => 'Pilihan yang mengizinkan memilih lebih dari satu opsi'],
            'camera_photo' => ['label' => 'Foto Kamera Tunggal (Wajib Kamera)', 'icon' => 'fa-camera', 'has_options' => false, 'hint' => 'Ambil 1 foto langsung dari kamera (galeri diblokir)'],
            'multi_photo' => ['label' => 'Multi-Foto Kamera (Before/After)', 'icon' => 'fa-images', 'has_options' => false, 'hint' => 'Ambil beberapa foto dokumentasi lapangan'],
            'signature' => ['label' => 'Tanda Tangan Digital (Signature Pad)', 'icon' => 'fa-signature', 'has_options' => false, 'hint' => 'Kanvas tanda tangan PIC / Store Manager'],
            'barcode_scanner' => ['label' => 'Scan Barcode / QR Code', 'icon' => 'fa-barcode', 'has_options' => false, 'hint' => 'Pindai barcode produk atau QR tag lokasi'],
            'month_year' => ['label' => 'Pilih Bulan & Tahun (MM/YYYY)', 'icon' => 'fa-calendar-days', 'has_options' => false, 'hint' => 'Format bulan dan tahun (misal: expired date)'],
            'date' => ['label' => 'Pilih Tanggal Lengkap (DD/MM/YYYY)', 'icon' => 'fa-calendar', 'has_options' => false, 'hint' => 'Pemilih kalender tanggal lengkap'],
            'time' => ['label' => 'Pilih Jam / Waktu', 'icon' => 'fa-clock', 'has_options' => false, 'hint' => 'Pemilih waktu / jam'],
            'rating_star' => ['label' => 'Rating Bintang (1-5)', 'icon' => 'fa-star', 'has_options' => false, 'hint' => 'Skala bintang 1 sampai 5'],
            'slider' => ['label' => 'Skala Slider (0-100)', 'icon' => 'fa-sliders', 'has_options' => false, 'hint' => 'Slider geser nilai numerik'],
            'gps_location' => ['label' => 'Koordinat GPS Otomatis', 'icon' => 'fa-location-dot', 'has_options' => false, 'hint' => 'Pinpoint latitude/longitude lokasi presisi'],
        ];
    }
}



