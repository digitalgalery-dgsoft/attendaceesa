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

        // Strictly scope data (Employees, Products, Submissions) to all division/branch codes of active entity
        $tenantPrincipalIds = Principal::where('name', $tenantPrincipal->name)
                                       ->where('is_active', true)
                                       ->pluck('id')
                                       ->toArray();

        if (empty($tenantPrincipalIds)) {
            $tenantPrincipalIds = [$tenantPrincipal->id];
        }

        $tenantPrincipalsAll = $request->attributes->get('tenant_principals_all')
                            ?? (app()->bound('current_tenant_principals_all') ? app('current_tenant_principals_all') : collect([$tenantPrincipal]));

        return [$tenantPrincipal, $tenantPrincipalIds, $tenantPrincipalsAll];
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
            return $emp->branch?->name ?? 'Pusat / Seluruh Area';
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
        $totalTemplateSubmissions = (clone $query)->count();
        $uniqueStores = (clone $query)->distinct('report_submissions.work_location_id')->count('report_submissions.work_location_id');

        $employees = Employee::whereIn('employees.principal_id', $scopedPrincipalIds)->orderBy('employees.full_name')->get();
        $workLocations = WorkLocation::whereIn('work_locations.principal_id', $scopedPrincipalIds)->orWhereNull('work_locations.principal_id')->orderBy('work_locations.name')->get();
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
     * View detailed individual report submission on Principal Portal
     */
    public function submissionDetail(Request $request, string $code, int $id)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

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

        $template = ReportTemplate::where('code', $code)
            ->whereHas('principals', function ($q) use ($scopedPrincipalIds) {
                $q->whereIn('principals.id', $scopedPrincipalIds);
            })
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

    /**
     * Master Data Products / SKU Catalog for Principal
     */
    public function productsList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);

        if (!$tenantPrincipal) {
            return redirect('/');
        }

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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
            fputcsv($handle, ['nama_produk', 'kode_sku', 'barcode', 'brand', 'kategori', 'harga', 'satuan', 'deskripsi']);

            // Sample rows
            fputcsv($handle, ['SoKlin Liquid Antibacterial 720ml', 'WNG-SKL-LIQ-720', '8998866101102', 'SoKlin', 'Care / Detergent', '19500', 'Pouch', 'Deterjen cair konsentrat antibakteri']);
            fputcsv($handle, ['Mie Sedaap Goreng Original 90g', 'WNG-MSD-GRG-90', '8998866200010', 'Mie Sedaap', 'Food & Beverage', '3200', 'Bks', 'Mie instan goreng bawang renyah']);
            fputcsv($handle, ['Nuvo Family Soap 76g', 'LNW-NVO-MRH-76', '8998866600015', 'Nuvo', 'Personal Care', '4500', 'Pcs', 'Sabun mandi antibakteri']);

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

        $assignedShiftIds = DB::table('employee_schedules')
            ->join('employees', 'employees.id', '=', 'employee_schedules.employee_id')
            ->whereIn('employees.principal_id', $scopedPrincipalIds)
            ->whereNotNull('employee_schedules.shift_id')
            ->pluck('employee_schedules.shift_id')
            ->unique()->filter()->values()->toArray();

        $query = Shift::query();
        if (!empty($assignedShiftIds)) {
            $query->whereIn('id', $assignedShiftIds);
        } else {
            $query->where('company_id', $tenantPrincipal->company_id);
        }

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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
    public function schedulesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.schedules', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'schedules', 'month', 'year', 'search', 'setting'
        ));
    }

    /**
     * Leave Requests (Izin / Cuti)
     */
    public function leavesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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
     * Visit Schedule (Itinerari)
     */
    public function itinerariesList(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

        $date = $request->query('date') ?? Carbon::today()->format('Y-m-d');
        $search = $request->query('q');

        $query = Itinerary::whereHas('employee', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('employees.principal_id', $scopedPrincipalIds);
        });

        if ($date) {
            $query->where('date', $date);
        }

        if ($search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $itineraries = $query->with(['employee.branch', 'items.workLocation'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        $brandColor = $tenantPrincipal->theme_color ?? '#0F52BA';
        $setting = Setting::first();

        return view('portal.itineraries', compact(
            'tenantPrincipal', 'tenantPrincipalsAll', 'brandColor', 'activeTemplates',
            'itineraries', 'date', 'search', 'setting'
        ));
    }

    /**
     * Manpower Report
     */
    public function manpowerReport(Request $request)
    {
        [$tenantPrincipal, $scopedPrincipalIds, $tenantPrincipalsAll] = $this->resolveTenant($request);
        if (!$tenantPrincipal) return redirect('/');

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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

        $activeTemplates = ReportTemplate::whereHas('principals', function ($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

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
