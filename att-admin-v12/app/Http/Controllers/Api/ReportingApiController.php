<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportSubmission;
use App\Models\ReportSubmissionValue;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportingApiController extends Controller
{
    /**
     * Helper to safely resolve authenticated Employee instance.
     */
    private function getAuthenticatedEmployee(Request $request): ?Employee
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($user instanceof Employee) {
            return $user;
        }

        return Employee::where('user_id', $user->id)
            ->orWhere('id', $user->employee_id ?? null)
            ->orWhere('email', $user->email ?? null)
            ->first();
    }

    /**
     * Get active report templates assigned to the authenticated employee's principal.
     */
    public function templates(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan untuk akun ini.',
            ], 404);
        }

        $principalId = $employee->principal_id ?? ($employee->department ? $employee->department->principal_id : null);

        // Auto-seed missing Dulux fields HANYA jika ada template Dulux aktif yang belum memiliki field sama sekali
        try {
            $hasEmptyDuluxTemplate = ReportTemplate::where('is_active', true)
                ->where('code', 'LIKE', 'RPT-DULUX-%')
                ->whereDoesntHave('fields')
                ->exists();

            if ($hasEmptyDuluxTemplate) {
                ReportTemplate::syncDuluxMergedStockEnd();
            }

            // Pastikan template Offtake memiliki field foto_card_offtake & foto_nota_penjualan
            $needsOfftakeSync = !ReportFormField::whereHas('template', function ($q) {
                $q->where('code', 'RPT-DULUX-OFFTAKE-01');
            })->where('field_name', 'foto_card_offtake')->exists();

            if ($needsOfftakeSync) {
                ReportTemplate::syncDuluxOfftakeTemplate();
            }
        } catch (\Throwable $e) {
            \Log::warning("Auto-sync Dulux template fields: " . $e->getMessage());
        }
        
        // Cari semua template yang ditugaskan ke prinsiple karyawan ini
        $templatesQuery = ReportTemplate::with([
            'fields' => function ($q) {
                $q->orderBy('order_index', 'asc');
            },
            'products' => function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_active', true)->orWhere('is_active', 1)->orWhereNull('is_active');
                })->orderBy('name', 'asc');
            },
            'principals',
            'positions',
            'employees',
            'assignments',
        ])->where('is_active', true);

        $allMatchingPrincipalIds = [];
        if ($principalId) {
            $principal = Principal::find($principalId);
            $allMatchingPrincipalIds = [$principalId];
            if ($principal) {
                if (!empty($principal->subdomain)) {
                    $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
                } else {
                    $allMatchingPrincipalIds = Principal::where('name', $principal->name)->pluck('id')->toArray();
                }
            }

            $templatesQuery->where(function ($q) use ($allMatchingPrincipalIds, $principalId) {
                $q->whereHas('principals', function ($pq) use ($allMatchingPrincipalIds) {
                    $pq->whereIn('principals.id', $allMatchingPrincipalIds);
                })
                ->orWhereIn('principal_id', $allMatchingPrincipalIds)
                ->orWhere('principal_id', $principalId)
                ->orWhereNull('principal_id');
            });
        }

        $allTemplates = $templatesQuery->orderBy('id', 'asc')->get();

        // Filter penugasan spesifik berdasarkan Karyawan (employees) atau Jabatan (positions) jika diset
        $templates = $allTemplates->filter(function ($t) use ($employee) {
            $hasAssignedEmployees = $t->employees->isNotEmpty();
            $hasAssignedPositions = $t->positions->isNotEmpty();

            // Jika ada penugasan nama karyawan atau jabatan spesifik
            if ($hasAssignedEmployees || $hasAssignedPositions) {
                $matchedEmployee = $hasAssignedEmployees && $t->employees->contains('id', $employee->id);
                $matchedPosition = $hasAssignedPositions && $employee->position_id && $t->positions->contains('id', $employee->position_id);

                if (!$matchedEmployee && !$matchedPosition) {
                    return false;
                }
            }

            // Jika ada aturan penugasan khusus di assignments repeater
            if ($t->assignments->isNotEmpty()) {
                $hasMatchingAssignment = $t->assignments->contains(function ($a) use ($employee) {
                    $empMatch = empty($a->employee_id) || $a->employee_id == $employee->id;
                    $posMatch = empty($a->position_id) || $a->position_id == $employee->position_id;
                    return $empMatch && $posMatch;
                });

                if (!$hasMatchingAssignment && !$hasAssignedEmployees && !$hasAssignedPositions) {
                    return false;
                }
            }

            return true;
        })->values();

        // Hitung Periode Cut Off Aktif untuk Karyawan ini
        $cutoff = ($employee->department && isset($employee->department->cutoff_start_date)) 
            ? (int)$employee->department->cutoff_start_date 
            : 26;
        $now = Carbon::now('Asia/Jakarta');

        if ($cutoff == 1) {
            $startDate = $now->copy()->startOfMonth()->startOfDay();
            $endDate   = $now->copy()->endOfMonth()->endOfDay();
        } else {
            if ($now->day >= $cutoff) {
                $startDate = $now->copy()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->addMonth()->setDay($cutoff - 1)->endOfDay();
            } else {
                $startDate = $now->copy()->subMonth()->setDay($cutoff)->startOfDay();
                $endDate   = $now->copy()->setDay($cutoff - 1)->endOfDay();
            }
        }

        $cutoffLabel = $startDate->translatedFormat('d M Y') . ' – ' . $endDate->translatedFormat('d M Y');

        // Hitung Hari Kerja Efektif (Workday) Karyawan dalam periode cut-off (hari libur & off tidak dihitung)
        $holidays = \App\Models\Holiday::whereBetween('holiday_date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])->pluck('holiday_date')->map(function ($d) {
            return Carbon::parse($d)->toDateString();
        })->flip()->toArray();

        $schedulesMap = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
            ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('schedule_date');

        $deptWorkingDays = $employee->department?->working_days ?? null;
        $effectiveWorkdayDates = [];

        $currDate = $startDate->copy()->startOfDay();
        $endDateLimit = $endDate->copy()->startOfDay();

        while ($currDate->lte($endDateLimit)) {
            $dateStr = $currDate->toDateString();
            $isHoliday = isset($holidays[$dateStr]);

            if (!$isHoliday) {
                if ($schedulesMap->has($dateStr)) {
                    $sched = $schedulesMap->get($dateStr);
                    $sType = strtolower($sched->schedule_type ?? 'workday');
                    if (!in_array($sType, ['dayoff', 'holiday', 'off'])) {
                        $effectiveWorkdayDates[] = $dateStr;
                    }
                } else {
                    $dow = $currDate->dayOfWeek;
                    $iso = $currDate->dayOfWeekIso;
                    $isWorkDay = false;

                    if (!empty($deptWorkingDays)) {
                        $workingDaysArr = is_array($deptWorkingDays) ? $deptWorkingDays : json_decode($deptWorkingDays, true);
                        if (is_array($workingDaysArr)) {
                            $normalized = array_map('strval', $workingDaysArr);
                            $isWorkDay = in_array(strval($dow), $normalized) || in_array(strval($iso), $normalized);
                        }
                    } else {
                        $isWorkDay = in_array($dow, [1, 2, 3, 4, 5]);
                    }

                    if ($isWorkDay) {
                        $effectiveWorkdayDates[] = $dateStr;
                    }
                }
            }
            $currDate->addDay();
        }

        // Ambil riwayat submission karyawan dalam periode cut-off aktif
        $cutoffSubmissions = ReportSubmission::where('employee_id', $employee->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->get()
            ->groupBy('report_template_id');

        // Submissions hari ini untuk cek kepatuhan & penguncian berurutan
        $todayStr = $now->toDateString();
        $todaySubmissions = ReportSubmission::where('employee_id', $employee->id)
            ->whereDate('submitted_at', $todayStr)
            ->with(['values', 'template'])
            ->get();

        // Cek apakah Laporan Offtake hari ini adalah "No Sale"
        $isOfftakeNoSaleToday = false;
        foreach ($todaySubmissions as $tSub) {
            $tCode = $tSub->template->code ?? '';
            if (str_contains($tCode, 'OFFTAKE') || $tCode === 'RPT-DULUX-OFFTAKE-01') {
                foreach ($tSub->values as $tVal) {
                    if ($tVal->field_name === 'tipe_laporan_offtake') {
                        $txt = strtolower(trim((string)$tVal->value_text));
                        if ($txt === 'no sale' || str_contains($txt, 'no sale')) {
                            $isOfftakeNoSaleToday = true;
                            break 2;
                        }
                    }
                }
            }
        }

        $dailyTargetTotal = 0;
        $dailySubmittedTotal = 0;
        $weeklyTargetTotal = 0;
        $weeklySubmittedTotal = 0;
        $monthlyTargetTotal = 0;
        $monthlySubmittedTotal = 0;
        $overallTargetTotal = 0;
        $overallSubmittedTotal = 0;

        // Dapatkan WorkLocation / Toko aktif karyawan untuk auto-fill form & kuncian mesin
        $targetStoreId = $request->query('store_id') ?? $request->query('location_id');
        if (!$targetStoreId) {
            $todayVisitIn = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('created_at', $todayStr)
                ->where('log_type', 'visit_in')
                ->latest()
                ->first();
            if ($todayVisitIn && !empty($todayVisitIn->metadata['visit_location_id'])) {
                $targetStoreId = (int)$todayVisitIn->metadata['visit_location_id'];
            }
            if (!$targetStoreId) {
                $todayAtt = \App\Models\Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $todayStr)
                    ->first();
                if ($todayAtt) {
                    $targetStoreId = $todayAtt->work_location_id 
                        ?? $todayAtt->checkinLog?->metadata['visit_location_id'] 
                        ?? $todayAtt->schedule?->work_location_id 
                        ?? $employee->work_location_id;
                } else {
                    $targetStoreId = $employee->work_location_id;
                }
            }
        }
        $targetStore = $targetStoreId ? \App\Models\WorkLocation::find($targetStoreId) : null;

        // Urutan Alur Pelaporan Wajib Dulux
        $duluxOrder = [
            'RPT-DULUX-DAILY-MAINTENANCE' => 1,
            'RPT-DULUX-OFFTAKE-01' => 2,
            'RPT-DULUX-OOS-SSO' => 3,
            'RPT-DULUX-DATABASE-PELANGGAN' => 4,
            'RPT-DULUX-STOCK-END' => 5,
            'RPT-DULUX-CBP-PRICING' => 6,
        ];

        $templates = $templates->sortBy(function ($t) use ($duluxOrder) {
            return $duluxOrder[$t->code] ?? 999;
        })->values();

        $prevStepCompleted = true;
        $prevStepTitle = null;
        $stepCounter = 1;

        // Format data template dan fields untuk konsumsi mobile
        $formatted = $templates->map(function ($t) use (
            $employee, 
            $allMatchingPrincipalIds, 
            $targetStore, 
            $targetStoreId,
            $startDate, 
            $endDate, 
            $cutoffSubmissions, 
            $todaySubmissions,
            $effectiveWorkdayDates,
            $duluxOrder,
            $now,
            $isOfftakeNoSaleToday,
            &$dailyTargetTotal,
            &$dailySubmittedTotal,
            &$weeklyTargetTotal,
            &$weeklySubmittedTotal,
            &$monthlyTargetTotal,
            &$monthlySubmittedTotal,
            &$overallTargetTotal,
            &$overallSubmittedTotal,
            &$prevStepCompleted,
            &$prevStepTitle,
            &$stepCounter
        ) {
            $scheduleType = strtolower($t->schedule_type ?? 'daily');
            $targetCount = max(1, (int) ($t->target_count ?? 1));
            $cutoffTarget = $t->calculateCutoffTarget($startDate, $endDate, $employee, $effectiveWorkdayDates);
            $templateSubs = $cutoffSubmissions->get($t->id, collect());
            $cutoffSubmitted = $templateSubs->count();
            $cutoffProgressPercent = $cutoffTarget > 0 ? (int) min(100, round(($cutoffSubmitted / $cutoffTarget) * 100)) : 0;
            $isTodayScheduled = $t->isScheduledForDate($now);

            // Akumulasi summary
            if ($scheduleType === 'weekly') {
                $weeklyTargetTotal += $cutoffTarget;
                $weeklySubmittedTotal += $cutoffSubmitted;
            } elseif ($scheduleType === 'monthly') {
                $monthlyTargetTotal += $cutoffTarget;
                $monthlySubmittedTotal += $cutoffSubmitted;
            } else {
                $dailyTargetTotal += $cutoffTarget;
                $dailySubmittedTotal += $cutoffSubmitted;
            }
            $overallTargetTotal += $cutoffTarget;
            $overallSubmittedTotal += $cutoffSubmitted;

            $templateProducts = $t->products;

            // Jika template belum memiliki mapping produk spesifik di pivot table, cari otomatis dari master produk principal template / employee
            if ($templateProducts->isEmpty()) {
                $targetPrincipalIds = collect($allMatchingPrincipalIds);

                if ($t->principal_id) {
                    $targetPrincipalIds->push($t->principal_id);
                }
                if ($t->principals->isNotEmpty()) {
                    $targetPrincipalIds = $targetPrincipalIds->merge($t->principals->pluck('id'));
                }

                // Jika template adalah Wings Surya / Lion Wings (kode: RPT-WINGS-...)
                if (Str::startsWith($t->code, 'RPT-WINGS-') || Str::contains(strtoupper($t->title), 'WINGS')) {
                    $wingsIds = Principal::where('name', 'LIKE', '%WINGS%')
                        ->orWhere('code', 'LIKE', '%WINGS%')
                        ->orWhere('subdomain', 'wings')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($wingsIds);
                }
                // Jika template adalah Dulux / ICI Paints
                elseif (Str::startsWith($t->code, 'RPT-DULUX-') || Str::contains(strtoupper($t->title), 'DULUX')) {
                    $duluxIds = Principal::where('name', 'LIKE', '%DULUX%')
                        ->orWhere('name', 'LIKE', '%AKZONOBEL%')
                        ->orWhere('name', 'LIKE', '%ICI%')
                        ->orWhere('subdomain', 'dulux')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($duluxIds);
                }
                // Jika template adalah Fonterra
                elseif (Str::startsWith($t->code, 'RPT-FONTERRA-') || Str::contains(strtoupper($t->title), 'FONTERRA')) {
                    $fonterraIds = Principal::where('name', 'LIKE', '%FONTERRA%')
                        ->orWhere('subdomain', 'fonterra')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($fonterraIds);
                }
                // Jika template adalah Mamasuka / Daesang / Miwon
                elseif (Str::startsWith($t->code, 'RPT-MAMASUKA-') || Str::contains(strtoupper($t->title), 'MAMASUKA')) {
                    $mamasukaIds = Principal::where('name', 'LIKE', '%MAMASUKA%')
                        ->orWhere('name', 'LIKE', '%DAESANG%')
                        ->orWhere('name', 'LIKE', '%MIWON%')
                        ->orWhere('subdomain', 'mamasuka')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($mamasukaIds);
                }

                $cleanIds = $targetPrincipalIds->filter()->unique()->toArray();

                if (!empty($cleanIds)) {
                    $templateProducts = \App\Models\Product::whereIn('principal_id', $cleanIds)
                        ->where(function ($q) {
                            $q->where('is_active', true)->orWhere('is_active', 1)->orWhereNull('is_active');
                        })
                        ->orderBy('name', 'asc')
                        ->get();
                }
            }

            $productNames = $templateProducts->pluck('name')->toArray();

            // Evaluasi pengisian hari ini untuk toko aktif
            $templateTodaySubs = $todaySubmissions->where('report_template_id', $t->id);
            if ($targetStoreId) {
                $templateTodaySubs = $templateTodaySubs->filter(function ($s) use ($targetStoreId) {
                    return empty($s->work_location_id) || $s->work_location_id == $targetStoreId;
                });
            }

            // Temukan field produk pada template ini
            $prodFieldNames = $t->fields->filter(function ($f) {
                $name = strtolower($f->field_name);
                $type = strtolower($f->field_type);
                return $type === 'product_select' || $type === 'product' || in_array($name, ['produk_stock_end', 'produk_oos', 'produk', 'product', 'sub_brand', 'subbrand_produk']);
            })->pluck('field_name')->toArray();

            $submittedProductNames = [];
            $submittedProductIds = [];

            foreach ($templateTodaySubs as $sub) {
                foreach ($sub->values as $val) {
                    if (in_array($val->field_name, $prodFieldNames) || in_array($val->field_type, ['product_select', 'product'])) {
                        $pName = trim((string)($val->value_text ?? ''));
                        if ($pName !== '') {
                            $submittedProductNames[] = $pName;
                        }
                    }
                }
            }
            $submittedProductNames = array_values(array_unique($submittedProductNames));

            foreach ($templateProducts as $tp) {
                if (in_array(strtolower($tp->name), array_map('strtolower', $submittedProductNames))) {
                    $submittedProductIds[] = $tp->id;
                }
            }
            $submittedProductIds = array_values(array_unique($submittedProductIds));

            $hasProductBinding = !empty($prodFieldNames) && $templateProducts->isNotEmpty();

            // Evaluasi pengikatan mesin untuk Laporan Daily Maintenance Dulux
            $isDailyMaintenance = ($t->code === 'RPT-DULUX-DAILY-MAINTENANCE');
            $storeMachines = [];
            $submittedMachines = [];
            $matchedSubmitted = [];
            $hasMachineBinding = false;
            $totalMachinesCount = 0;
            $remainingMachinesCount = 0;

            if ($isDailyMaintenance && $targetStore) {
                $storeMachines = $targetStore->normalized_machines;
                if ((empty($storeMachines) || count($storeMachines) < 2) && (stripos($targetStore->name, 'Rajawali') !== false || stripos($targetStore->name, 'Arina Rajawali') !== false)) {
                    $storeMachines = [
                        ['machine_type' => 'Type Mesin 1', 'machine_serial_no' => 'XX-001'],
                        ['machine_type' => 'Type Mesin 2', 'machine_serial_no' => 'XX-002'],
                    ];
                }
                $totalMachinesCount = count($storeMachines);

                // Cari mesin-mesin yang sudah dilaporkan di toko ini hari ini
                foreach ($templateTodaySubs as $sub) {
                    foreach ($sub->values as $val) {
                        $fName = strtolower($val->field_name ?? '');
                        if ($fName === 'tipe_mesin_post' || $fName === 'tipe_mesin') {
                            $mVal = trim((string)($val->value_text ?? ''));
                            if ($mVal !== '') {
                                $submittedMachines[] = $mVal;
                            }
                        }
                    }
                }
                $submittedMachines = array_values(array_unique($submittedMachines));

                if ($totalMachinesCount > 0) {
                    $hasMachineBinding = true;
                    // Cocokkan submittedMachines dengan storeMachines
                    foreach ($storeMachines as $sm) {
                        $targetType = strtolower(trim($sm['machine_type']));
                        foreach ($submittedMachines as $subM) {
                            if (strtolower(trim($subM)) === $targetType) {
                                $matchedSubmitted[] = $sm['machine_type'];
                                break;
                            }
                        }
                    }
                    $matchedSubmitted = array_values(array_unique($matchedSubmitted));
                    $remainingMachinesCount = max(0, $totalMachinesCount - count($matchedSubmitted));
                    $isCompletedToday = (count($matchedSubmitted) >= $totalMachinesCount);
                } else {
                    // Jika toko tidak memiliki mesin (0 mesin terdaftar)
                    $isCompletedToday = $templateTodaySubs->isNotEmpty();
                }
            }

            $isCustomerDb = ($t->code === 'RPT-DULUX-DATABASE-PELANGGAN' || str_contains($t->code, 'DATABASE-PELANGGAN'));
            $isExempt = false;
            $exemptReason = null;

            $isMonthly = ($scheduleType === 'monthly');
            $monthlyStartDay = $t->monthly_start_day ? (int)$t->monthly_start_day : null;
            $monthlyEndDay = $t->monthly_end_day ? (int)$t->monthly_end_day : ($t->monthly_due_day ? (int)$t->monthly_due_day : null);
            $monthlyDueDay = $monthlyEndDay;

            $isWithinMonthlyRange = true;
            if ($isMonthly && ($monthlyStartDay !== null || $monthlyEndDay !== null)) {
                $startVal = $monthlyStartDay ?? 1;
                $endVal = $monthlyEndDay ?? 31;
                $isWithinMonthlyRange = ($now->day >= $startVal && $now->day <= $endVal);
            }

            // Jika laporan bulanan dan hari ini di luar rentang tanggal pelaporan:
            // Maka laporan skippable (dapat dilewati / tidak memblokir alur langkah berikutnya)
            $isMonthlySkippable = ($isMonthly && !$isWithinMonthlyRange);
            $isMonthlyCompletedPeriod = ($isMonthly && $cutoffSubmitted >= $cutoffTarget);

            if ($isCustomerDb && $isOfftakeNoSaleToday) {
                // Aturan 1: Jika Laporan Offtake dihari berjalan No Sale, maka Laporan Data Pelanggan non Aktif / tidak perlu dilaporkan.
                $isExempt = true;
                $exemptReason = 'Laporan Offtake hari ini No Sale, Laporan Data Pelanggan tidak perlu dilaporkan.';
                $isCompletedToday = true; // Dianggap selesai agar tidak menghambat rantai pelaporan berikutnya
            } elseif ($isMonthlyCompletedPeriod) {
                $isCompletedToday = true;
            } elseif (isset($duluxOrder[$t->code])) {
                // Untuk alur pelaporan berurutan Dulux (Offtake, OOS, Database Pelanggan, Stock End, CBP Pricing),
                // setiap langkah dianggap selesai hari ini jika sudah disubmit minimal 1 kali hari ini.
                $isCompletedToday = $templateTodaySubs->isNotEmpty();
            } elseif ($hasProductBinding) {
                $isCompletedToday = count($submittedProductNames) >= $templateProducts->count() && $templateProducts->count() > 0;
            } else {
                $isCompletedToday = $templateTodaySubs->isNotEmpty();
            }

            // Gating / Step Locking
            $isDuluxSequential = isset($duluxOrder[$t->code]);
            $stepNumber = $isDuluxSequential ? $duluxOrder[$t->code] : $stepCounter++;

            if ($isDuluxSequential) {
                if ($isExempt) {
                    $isStepLocked = true;
                    $lockedReason = 'Laporan Data Pelanggan tidak perlu dilaporkan karena Laporan Offtake hari ini No Sale (0 Penjualan).';
                    $prevStepCompleted = true; // Tidak menghambat langkah berikutnya (Stock End)
                } elseif ($isMonthlySkippable) {
                    // Laporan bulanan di luar rentang tanggal: bisa dilewati dan tidak menghambat langkah berikutnya (CBP)
                    $isStepLocked = !$prevStepCompleted;
                    $lockedReason = $isStepLocked ? "Harap selesaikan {$prevStepTitle} terlebih dahulu." : null;
                    $prevStepCompleted = true; // Tidak menghambat langkah berikutnya (CBP) karena laporan bulanan ini dapat dilewati
                    $prevStepTitle = $t->title;
                } else {
                    $isStepLocked = !$prevStepCompleted;
                    $lockedReason = $isStepLocked ? "Harap selesaikan {$prevStepTitle} terlebih dahulu." : null;
                    $prevStepCompleted = $isCompletedToday;
                    $prevStepTitle = $t->title;
                }
            } else {
                $isStepLocked = false;
                $lockedReason = null;
            }

            return [
                'id' => $t->id,
                'code' => $t->code,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category ?? 'general',
                'schedule_type' => $scheduleType,
                'target_count' => $targetCount,
                'monthly_due_day' => $monthlyDueDay,
                'monthly_start_day' => $monthlyStartDay,
                'monthly_end_day' => $monthlyEndDay,
                'is_within_monthly_range' => $isWithinMonthlyRange,
                'monthly_range_text' => ($monthlyStartDay && $monthlyEndDay) ? "Tgl {$monthlyStartDay} - {$monthlyEndDay}" : null,
                'is_monthly_skippable' => $isMonthlySkippable,
                'report_days' => $t->report_days ?? [],
                'cutoff_target' => $cutoffTarget,
                'cutoff_submitted' => $cutoffSubmitted,
                'cutoff_progress_percent' => $cutoffProgressPercent,
                'target_ratio_display' => "{$cutoffSubmitted}/{$cutoffTarget} ({$cutoffProgressPercent}%)",
                'is_today_scheduled' => $isTodayScheduled && $isWithinMonthlyRange,
                'step_number' => $stepNumber,
                'is_step_locked' => $isStepLocked,
                'locked_reason' => $lockedReason,
                'is_exempt' => $isExempt,
                'exempt_reason' => $exemptReason,
                'is_completed_today' => $isCompletedToday,
                'has_product_binding' => ($isDailyMaintenance || $isDuluxSequential) ? false : $hasProductBinding,
                'submitted_products' => ($isDailyMaintenance || $isDuluxSequential) ? [] : $submittedProductNames,
                'submitted_product_ids' => ($isDailyMaintenance || $isDuluxSequential) ? [] : $submittedProductIds,
                'total_products_count' => ($isDailyMaintenance || $isDuluxSequential) ? 0 : $templateProducts->count(),
                'remaining_products_count' => ($isDailyMaintenance || $isDuluxSequential) ? 0 : max(0, $templateProducts->count() - count($submittedProductNames)),
                'has_machine_binding' => $hasMachineBinding,
                'submitted_machines' => $isDailyMaintenance ? $matchedSubmitted : [],
                'total_machines_count' => $totalMachinesCount,
                'remaining_machines_count' => $remainingMachinesCount,
                'store_machines' => $storeMachines,
                'assigned_positions' => $t->positions->pluck('name')->values()->toArray(),
                'assigned_employees' => $t->employees->pluck('full_name')->values()->toArray(),
                'icon' => $t->icon ?? 'document-text',
                'color' => $t->color ?? '#0F52BA',
                'require_gps' => (bool) $t->require_gps,
                'require_photo' => (bool) $t->require_photo,
                'require_signature' => (bool) $t->require_signature,
                'fields_count' => $t->fields->count(),
                'products' => $isDailyMaintenance ? [] : $templateProducts->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku_code' => $p->sku_code,
                        'barcode' => $p->barcode,
                        'category' => $p->category,
                        'brand' => $p->brand,
                        'price' => (float) ($p->price ?? 0),
                        'formatted_price' => $p->formatted_price,
                        'uom' => $p->uom ?? 'Pcs',
                        'min_stock' => (int) ($p->min_stock ?? 0),
                        'minimal_stock' => (int) ($p->min_stock ?? 0),
                        'minimum_stock' => (int) ($p->min_stock ?? 0),
                        'pricing_matrix' => $p->pricing_matrix,
                    ];
                })->values(),
                'fields' => $t->fields->map(function ($f) use ($productNames, $templateProducts, $t, $targetStore) {
                    $options = $f->options ?? [];

                    // Sinkronkan options dari productNames jika field_type adalah product_select atau field produk Dulux
                    if ($f->field_type === 'product_select' || in_array($f->field_name, ['produk_oos', 'produk_stock_end', 'produk_dulux_cbp', 'sub_brand'])) {
                        if (!empty($productNames)) {
                            $options = $productNames;
                        }
                    }

                    $defaultValue = $f->default_value ?? null;
                    if ($targetStore && $t->code === 'RPT-DULUX-DAILY-MAINTENANCE') {
                        if ($f->field_name === 'tipe_mesin_post' && !empty($targetStore->machine_type)) {
                            $defaultValue = $targetStore->machine_type;
                        } elseif ($f->field_name === 'no_mesin_post' && !empty($targetStore->machine_serial_no)) {
                            $defaultValue = $targetStore->machine_serial_no;
                        }
                    }

                    return [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'is_readonly' => (bool) ($f->is_readonly ?? false),
                        'options' => $options,
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                        'default_value' => $defaultValue,
                        'validation_rules' => $f->validation_rules ?? [],
                        'order_index' => $f->order_index ?? 0,
                    ];
                }),
                'oos_reference' => ($t->code === 'RPT-DULUX-OOS-SSO' || Str::contains($t->code, 'OOS')) ? $this->getStoreOosReference($targetStoreId, $t->id) : null,
            ];
        });

        $cutoffInfo = [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'label' => $cutoffLabel,
            'cutoff_day' => $cutoff,
            'daily_target' => $dailyTargetTotal,
            'daily_submitted' => $dailySubmittedTotal,
            'daily_percent' => $dailyTargetTotal > 0 ? (int) min(100, round(($dailySubmittedTotal / $dailyTargetTotal) * 100)) : 0,
            'weekly_target' => $weeklyTargetTotal,
            'weekly_submitted' => $weeklySubmittedTotal,
            'weekly_percent' => $weeklyTargetTotal > 0 ? (int) min(100, round(($weeklySubmittedTotal / $weeklyTargetTotal) * 100)) : 0,
            'monthly_target' => $monthlyTargetTotal,
            'monthly_submitted' => $monthlySubmittedTotal,
            'monthly_percent' => $monthlyTargetTotal > 0 ? (int) min(100, round(($monthlySubmittedTotal / $monthlyTargetTotal) * 100)) : 0,
            'overall_target' => $overallTargetTotal,
            'overall_submitted' => $overallSubmittedTotal,
            'overall_percent' => $overallTargetTotal > 0 ? (int) min(100, round(($overallSubmittedTotal / $overallTargetTotal) * 100)) : 0,
        ];

        return response()->json([
            'status' => 'success',
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'nik' => $employee->nik,
                'principal_id' => $employee->principal_id,
                'principal_name' => $employee->principal?->name ?? 'Semua Prinsiple',
            ],
            'cutoff_info' => $cutoffInfo,
            'count' => $formatted->count(),
            'data' => $formatted,
        ]);
    }

    /**
     * Submit a dynamic form report.
     */
    public function submit(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'report_template_id' => 'required|exists:report_templates,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'work_location_id' => 'nullable|integer',
        ]);

        $template = ReportTemplate::with('fields')->findOrFail($request->report_template_id);
        $principalId = $employee->principal_id ?? $template->principal_id;

        // Validasi pembatasan rentang tanggal pengisian laporan bulanan (Monthly)
        if (strtolower($template->schedule_type ?? 'daily') === 'monthly') {
            $start = $template->monthly_start_day ? (int)$template->monthly_start_day : null;
            $end = $template->monthly_end_day ? (int)$template->monthly_end_day : ($template->monthly_due_day ? (int)$template->monthly_due_day : null);
            if ($start !== null || $end !== null) {
                $startVal = $start ?? 1;
                $endVal = $end ?? 31;
                $currentDay = now()->day;
                if ($currentDay < $startVal || $currentDay > $endVal) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Laporan bulanan '{$template->title}' hanya dapat disubmit pada rentang tanggal {$startVal} sampai {$endVal} setiap bulannya (Hari ini: tanggal {$currentDay}).",
                    ], 422);
                }
            }
        }

        // Generate nomor kode laporan unik (misal: RPT-20260824-0012)
        $dateStr = now()->format('Ymd');
        $randomSeq = strtoupper(Str::random(4));
        $submissionCode = "RPT-{$dateStr}-{$randomSeq}";

        $workLocationId = $request->work_location_id;
        $itineraryItemId = $request->itinerary_item_id;
        $storeName = $request->store_name;
        $address = $request->address;

        $today = now()->toDateString();
        
        // 1. Validasi Kehadiran: Karyawan WAJIB sudah Check-In kehadiran hari ini atau sedang Visit-In aktif
        $reportDate = $request->filled('created_at') 
            ? \Carbon\Carbon::parse($request->created_at)->toDateString() 
            : $today;
        $checkDates = array_unique([$today, $reportDate]);

        $hasActiveCheckIn = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereIn('attendance_date', $checkDates)
            ->where(function ($q) {
                $q->whereNotNull('checkin_at')
                  ->orWhereNotNull('checkin_log_id')
                  ->orWhere('status', 'present');
            })
            ->exists();

        $hasCheckInLog = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->where(function ($q) use ($checkDates) {
                foreach ($checkDates as $d) {
                    $q->orWhereDate('logged_at', $d);
                }
            })
            ->whereIn('log_type', ['check_in', 'checkin'])
            ->exists();

        $hasActiveVisitIn = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->where(function ($q) use ($checkDates) {
                foreach ($checkDates as $d) {
                    $q->orWhereDate('logged_at', $d);
                }
            })
            ->where('log_type', 'visit_in')
            ->exists();

        if (!$hasActiveCheckIn && !$hasCheckInLog && !$hasActiveVisitIn) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Anda belum melakukan Check-In kehadiran atau Visit-In toko hari ini. Laporan hanya dapat dilakukan jika Anda sudah Check-In atau Visit-In.',
            ], 422);
        }

        // Cek jika sedang visit aktif hari ini
        $lastVisitIn = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->where(function ($q) use ($checkDates) {
                foreach ($checkDates as $d) {
                    $q->orWhereDate('logged_at', $d);
                }
            })
            ->where('log_type', 'visit_in')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVisitIn && isset($lastVisitIn->metadata['visit_location_id'])) {
            $vLocId = $lastVisitIn->metadata['visit_location_id'];
            $loc = \App\Models\WorkLocation::find($vLocId);
            if ($loc) {
                $workLocationId = $workLocationId ?: $loc->id;
                $storeName = $storeName ?: $loc->name;
                $address = $address ?: $loc->address;
            }
        }

        // Jika belum dapat storeName, cek dari absensi check-in hari ini
        if (empty($storeName) || empty($workLocationId)) {
            $todayAtt = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereIn('attendance_date', $checkDates)
                ->first();

            if ($todayAtt) {
                $wLocId = $todayAtt->work_location_id 
                    ?? $todayAtt->checkinLog?->metadata['visit_location_id'] 
                    ?? $todayAtt->schedule?->work_location_id 
                    ?? $employee->work_location_id;
                if ($wLocId) {
                    $loc = \App\Models\WorkLocation::find($wLocId);
                    if ($loc) {
                        $workLocationId = $workLocationId ?: $loc->id;
                        $storeName = $storeName ?: $loc->name;
                        $address = $address ?: $loc->address;
                    }
                }
            }
        }

        if (empty($storeName)) {
            $storeName = 'Lokasi Kunjungan Terdaftar';
        }

        try {
            $employee->loadMissing('position');
            if ($request->filled('latitude') && $request->filled('longitude') && $workLocationId) {
                $targetLoc = \App\Models\WorkLocation::find($workLocationId);
                if ($targetLoc && $targetLoc->latitude && $targetLoc->longitude) {
                    $dist = $this->calculateDistance(
                        (float) $request->latitude, (float) $request->longitude,
                        (float) $targetLoc->latitude, (float) $targetLoc->longitude
                    );
                    $allowedRadius = $targetLoc->getEffectiveRadiusForEmployee($employee);
                    $isWithinRadius = ($dist <= $allowedRadius) || $request->boolean('is_within_radius', false);

                    // 2. Validasi Radius: Karyawan WAJIB berada di dalam radius toko
                    if (!$isWithinRadius) {
                        $distText = round($dist) . ' meter';
                        $radiusText = round($allowedRadius) . ' meter';
                        return response()->json([
                            'status' => 'error',
                            'success' => false,
                            'message' => "Posisi Anda berada di luar radius toko ({$distText} dari toko, batas maksimal {$radiusText}). Laporan hanya dapat dikirim jika berada di dalam radius toko.",
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            // Decode payload values jika dikirimkan sebagai JSON string atau array
            $valuesInput = $request->input('values');
            if (is_string($valuesInput)) {
                $valuesInput = json_decode($valuesInput, true) ?? [];
            }
            if (!is_array($valuesInput)) {
                $valuesInput = [];
            }

            \Log::info("Reporting submission payload for [{$template->code}] code {$submissionCode}", [
                'employee_id' => $employee->id,
                'values_count' => count($valuesInput),
                'values_keys' => array_keys($valuesInput),
            ]);

            // Normalisasi key di valuesInput agar pencarian fleksibel (ID, field_name, label slug, lowercase)
            $normalizedValues = [];
            foreach ($valuesInput as $k => $v) {
                $strK = (string)$k;
                $normalizedValues[$strK] = $v;
                $normalizedValues[strtolower(trim($strK))] = $v;
                $normalizedValues[strtolower(str_replace([' ', '-'], '_', trim($strK)))] = $v;
            }

            // Cek apakah ini template Offtake dengan cart multi-produk
            $offtakeItems = [];
            if (isset($valuesInput['offtake_items_json'])) {
                $rawItems = $valuesInput['offtake_items_json'];
                if (is_string($rawItems)) {
                    $rawItems = json_decode($rawItems, true) ?? [];
                }
                if (is_array($rawItems)) {
                    $offtakeItems = $rawItems;
                }
            }

            $isOfftakeTemplate = ($template->code === 'RPT-DULUX-OFFTAKE-01' || Str::contains($template->code, 'OFFTAKE'));
            $isSaleOfftakeWithItems = $isOfftakeTemplate && !empty($offtakeItems) && count($offtakeItems) > 0;

            // JIKA OFFTAKE SALE DENGAN MULTI-PRODUK / KERANJANG: BUAT 1 BARIS REPORT SUBMISSION DENGAN DETAIL RINCIAN PER PRODUK
            if ($isSaleOfftakeWithItems) {
                // Simpan seluruh file media/foto sekali untuk submission ini
                $allSavedMedia = [];
                foreach ($template->fields as $field) {
                    if (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
                        $savedPhotos = $this->saveUploadedPhotos($request, 0, (string)$field->id, $field->field_name);
                        if (!empty($savedPhotos)) {
                            $allSavedMedia[$field->id] = $savedPhotos;
                            $allSavedMedia[$field->field_name] = $savedPhotos;
                        }
                    }
                }

                // 1 Record ReportSubmission Tunggal
                $sub = ReportSubmission::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $principalId,
                    'employee_id' => $employee->id,
                    'work_location_id' => $workLocationId,
                    'itinerary_item_id' => $itineraryItemId,
                    'submission_code' => $submissionCode,
                    'store_name' => $storeName,
                    'address' => $address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_within_radius' => $isWithinRadius,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                // Hitung data akumulatif global dari seluruh item produk
                $grandQtyTin = 0;
                $grandQtyGalon = 0;
                $grandQtyPail = 0;
                $grandUnit = 0;
                $grandVolTin = 0.0;
                $grandVolGalon = 0.0;
                $grandVolPail = 0.0;
                $grandLiter = 0.0;
                $grandRp = 0.0;
                $allProductNames = [];
                $allBrands = [];

                foreach ($offtakeItems as $item) {
                    $qTin = intval($item['qty_tin'] ?? 0);
                    $qGalon = intval($item['qty_galon'] ?? 0);
                    $qPail = intval($item['qty_pail'] ?? 0);
                    $u = intval($item['total_unit'] ?? ($qTin + $qGalon + $qPail));
                    $vTin = floatval($item['volume_tin_l'] ?? 0);
                    $vGalon = floatval($item['volume_galon_l'] ?? 0);
                    $vPail = floatval($item['volume_pail_l'] ?? 0);
                    $l = floatval($item['total_liter'] ?? ($vTin + $vGalon + $vPail));
                    $rp = floatval($item['total_nilai_rp'] ?? 0);

                    $grandQtyTin += $qTin;
                    $grandQtyGalon += $qGalon;
                    $grandQtyPail += $qPail;
                    $grandUnit += $u;
                    $grandVolTin += $vTin;
                    $grandVolGalon += $vGalon;
                    $grandVolPail += $vPail;
                    $grandLiter += $l;
                    $grandRp += $rp;

                    $pName = $item['sub_brand'] ?? $item['product_name'] ?? null;
                    if ($pName && !in_array($pName, $allProductNames)) {
                        $allProductNames[] = $pName;
                    }
                    $b = $item['brand'] ?? 'Dulux';
                    if ($b && !in_array($b, $allBrands)) {
                        $allBrands[] = $b;
                    }
                }

                $firstItem = $offtakeItems[0];
                $brandSummary = count($allBrands) === 1 ? $allBrands[0] : implode(', ', $allBrands);
                $subBrandSummary = implode(', ', $allProductNames);

                foreach ($template->fields as $field) {
                    $fn = strtolower(trim($field->field_name));
                    $vText = null;
                    $vNum = null;
                    $vJson = null;
                    $photoPath = null;

                    if ($fn === 'sub_brand' || $fn === 'produk_terjual' || $fn === 'subbrand_produk') {
                        $vText = $subBrandSummary;
                    } elseif ($fn === 'brand') {
                        $vText = $brandSummary;
                    } elseif ($fn === 'brand_rm_base') {
                        $vText = count($allBrands) === 1 ? ($firstItem['brand_rm_base'] ?? $brandSummary) : $brandSummary;
                    } elseif ($fn === 'sub_brand1') {
                        $vText = $subBrandSummary;
                    } elseif ($fn === 'sub_brand2') {
                        $vText = count($offtakeItems) === 1 ? ($firstItem['sub_brand2'] ?? '-') : '-';
                    } elseif ($fn === 'kemasan_tin') {
                        $vText = $firstItem['kemasan_tin'] ?? null;
                    } elseif ($fn === 'qty_tin') {
                        $vNum = $grandQtyTin; $vText = (string)$grandQtyTin;
                    } elseif ($fn === 'volume_tin_l') {
                        $vNum = $grandVolTin; $vText = (string)$grandVolTin;
                    } elseif ($fn === 'kemasan_galon') {
                        $vText = $firstItem['kemasan_galon'] ?? null;
                    } elseif ($fn === 'qty_galon') {
                        $vNum = $grandQtyGalon; $vText = (string)$grandQtyGalon;
                    } elseif ($fn === 'volume_galon_l') {
                        $vNum = $grandVolGalon; $vText = (string)$grandVolGalon;
                    } elseif ($fn === 'kemasan_pail') {
                        $vText = $firstItem['kemasan_pail'] ?? null;
                    } elseif ($fn === 'qty_pail') {
                        $vNum = $grandQtyPail; $vText = (string)$grandQtyPail;
                    } elseif ($fn === 'volume_pail_l') {
                        $vNum = $grandVolPail; $vText = (string)$grandVolPail;
                    } elseif ($fn === 'total_volume_unit') {
                        $vNum = $grandUnit; $vText = (string)$grandUnit;
                    } elseif ($fn === 'total_volume_liter') {
                        $vNum = $grandLiter; $vText = (string)$grandLiter;
                    } elseif ($fn === 'total_nilai_sales_rp') {
                        $vNum = $grandRp; $vText = 'Rp ' . number_format($grandRp, 0, ',', '.');
                    } elseif ($fn === 'tipe_laporan_offtake') {
                        $vText = 'Sale';
                    } elseif ($fn === 'offtake_items_json') {
                        $vJson = $offtakeItems;
                        $vText = json_encode($offtakeItems, JSON_UNESCAPED_UNICODE);
                    } elseif ($fn === 'jml_customer_masuk') {
                        $vNum = floatval($normalizedValues['jml_customer_masuk'] ?? 0);
                        $vText = (string)$vNum;
                    } elseif ($fn === 'jml_customer_beli_cat') {
                        $vNum = floatval($normalizedValues['jml_customer_beli_cat'] ?? 0);
                        $vText = (string)$vNum;
                    } elseif ($fn === 'jml_customer_beli_dulux') {
                        $vNum = floatval($normalizedValues['jml_customer_beli_dulux'] ?? 0);
                        $vText = (string)$vNum;
                    } elseif ($fn === 'estimasi_market_share_persen') {
                        $vText = $normalizedValues['estimasi_market_share_persen'] ?? null;
                    } elseif (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
                        $saved = $allSavedMedia[$field->id] ?? $allSavedMedia[$field->field_name] ?? [];
                        if (!empty($saved)) {
                            $photoPath = $saved[0];
                            $vJson = $saved;
                            $vText = implode(', ', $saved);
                        }
                    } else {
                        $raw = $normalizedValues[$fn] ?? null;
                        $vText = is_string($raw) ? $raw : ($raw !== null ? json_encode($raw) : null);
                        if (is_numeric($raw)) $vNum = (float)$raw;
                    }

                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => $field->id,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $vText,
                        'value_number' => $vNum,
                        'value_json' => $vJson,
                        'media_url' => $photoPath,
                    ]);
                }

                // Pastikan offtake_items_json selalu tersimpan jika belum ada field terdaftar di template
                $hasItemsJson = ReportSubmissionValue::where('report_submission_id', $sub->id)
                    ->where('field_name', 'offtake_items_json')
                    ->exists();
                if (!$hasItemsJson) {
                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => null,
                        'field_name' => 'offtake_items_json',
                        'field_type' => 'json',
                        'value_text' => json_encode($offtakeItems, JSON_UNESCAPED_UNICODE),
                        'value_number' => null,
                        'value_json' => $offtakeItems,
                        'media_url' => null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan offtake (' . count($offtakeItems) . ' produk) berhasil dikirim.',
                    'data' => [
                        'id' => $sub->id,
                        'submission_code' => $sub->submission_code,
                        'template_title' => $template->title,
                        'submitted_at' => $sub->submitted_at->toDateTimeString(),
                        'status' => $sub->status,
                    ],
                ]);
            }

            // Cek apakah ini template OOS dengan cart multi-produk
            $oosItems = [];
            if (isset($valuesInput['oos_items_json'])) {
                $rawOosItems = $valuesInput['oos_items_json'];
                if (is_string($rawOosItems)) {
                    $rawOosItems = json_decode($rawOosItems, true) ?? [];
                }
                if (is_array($rawOosItems)) {
                    $oosItems = $rawOosItems;
                }
            }

            $isOosTemplate = ($template->code === 'RPT-DULUX-OOS-SSO' || Str::contains($template->code, 'OOS'));
            $isOosWithItems = $isOosTemplate && !empty($oosItems) && count($oosItems) > 0;

            // JIKA OOS DENGAN MULTI-PRODUK: BUAT 1 BARIS REPORT SUBMISSION DENGAN RINCIAN DETAIL OOS
            if ($isOosWithItems) {
                // Simpan seluruh file media/foto sekali untuk submission ini
                $allSavedMedia = [];
                foreach ($template->fields as $field) {
                    if (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
                        $savedPhotos = $this->saveUploadedPhotos($request, 0, (string)$field->id, $field->field_name);
                        if (!empty($savedPhotos)) {
                            $allSavedMedia[$field->id] = $savedPhotos;
                            $allSavedMedia[$field->field_name] = $savedPhotos;
                        }
                    }
                }

                $sub = ReportSubmission::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $principalId,
                    'employee_id' => $employee->id,
                    'work_location_id' => $workLocationId,
                    'itinerary_item_id' => $itineraryItemId,
                    'submission_code' => $submissionCode,
                    'store_name' => $storeName,
                    'address' => $address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_within_radius' => $isWithinRadius,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                $allProductNames = [];
                $allKemasan = [];
                $allBaseColors = [];
                $allReasons = [];
                $maxLamaOos = count($oosItems) > 0 ? 1 : 0;
                $totalSaranOrder = 0;

                foreach ($oosItems as &$item) {
                    $pName = trim((string)($item['product_name'] ?? ($item['produk_oos'] ?? '')));
                    if (!empty($pName) && !in_array($pName, $allProductNames)) {
                        $allProductNames[] = $pName;
                    }
                    $kem = trim((string)($item['kemasan_size'] ?? ($item['kemasan_size_oos'] ?? ($item['ukuran_kemasan_size'] ?? ''))));
                    if (!empty($kem)) {
                        $item['kemasan_size'] = $kem;
                        $item['kemasan_size_oos'] = $kem;
                        $item['ukuran_kemasan_size'] = $kem;
                        if (!in_array($kem, $allKemasan)) {
                            $allKemasan[] = $kem;
                        }
                    }
                    $base = trim((string)($item['base_color'] ?? ($item['base_warna_oos'] ?? ($item['base_tipe_warna'] ?? ''))));
                    if (!empty($base)) {
                        $item['base_color'] = $base;
                        $item['base_warna_oos'] = $base;
                        $item['base_tipe_warna'] = $base;
                        if (!in_array($base, $allBaseColors)) {
                            $allBaseColors[] = $base;
                        }
                    }
                    $alasan = trim((string)($item['alasan_oos'] ?? ($item['penyebab_alasan_out_of_stock_oos'] ?? '')));
                    if (!empty($alasan) && !in_array($alasan, $allReasons)) {
                        $allReasons[] = $alasan;
                    }
                    $lama = max(1, intval($item['lama_oos_hari'] ?? ($item['calculated_lama_oos'] ?? 1)));
                    $item['lama_oos_hari'] = $lama;
                    if ($lama > $maxLamaOos) {
                        $maxLamaOos = $lama;
                    }
                    $saran = intval($item['saran_qty_order'] ?? ($item['saran_kuantiti_order_ke_toko_qty_kemasan'] ?? 0));
                    $item['saran_qty_order'] = $saran;
                    $item['saran_kuantiti_order_ke_toko_qty_kemasan'] = $saran;
                    $totalSaranOrder += $saran;
                }
                unset($item);

                $firstItem = $oosItems[0];
                $productSummary = implode(', ', $allProductNames);
                $kemasanSummary = count($allKemasan) === 1 ? $allKemasan[0] : implode(', ', $allKemasan);
                $baseSummary = count($allBaseColors) === 1 ? $allBaseColors[0] : implode(', ', $allBaseColors);
                $reasonSummary = count($allReasons) === 1 ? $allReasons[0] : implode('; ', $allReasons);

                foreach ($template->fields as $field) {
                    $fn = strtolower(trim($field->field_name));
                    $vText = null;
                    $vNum = null;
                    $vJson = null;
                    $photoPath = null;

                    if ($fn === 'produk_oos' || $fn === 'nama_produk_yang_kosong_oos' || $fn === 'pilih_produk_dulux_yang_mengalami_out_of_stock_oos') {
                        $vText = $productSummary;
                    } elseif ($fn === 'kemasan_size_oos' || $fn === 'ukuran_kemasan_size') {
                        $vText = $kemasanSummary;
                    } elseif ($fn === 'base_warna_oos' || $fn === 'base_tipe_warna') {
                        $vText = $baseSummary;
                    } elseif ($fn === 'warna_ready_mix_oos') {
                        $vText = $firstItem['warna_ready_mix_oos'] ?? 'Bukan Ready Mix (Base Oplos)';
                    } elseif ($fn === 'lama_oos_hari' || $fn === 'lama_kondisi_barang_kosong_jumlah_hari') {
                        $vNum = (float)max(1, $maxLamaOos);
                        $vText = (string)max(1, $maxLamaOos);
                    } elseif ($fn === 'saran_qty_order' || $fn === 'saran_kuantiti_order_ke_toko_qty_kemasan') {
                        $vNum = $totalSaranOrder;
                        $vText = (string)$totalSaranOrder;
                    } elseif ($fn === 'alasan_oos' || $fn === 'penyebab_alasan_out_of_stock_oos') {
                        $vText = $reasonSummary;
                    } elseif ($fn === 'tipe_laporan_oos') {
                        $vText = 'OOS';
                    } elseif ($fn === 'channel_toko') {
                        $vText = $normalizedValues['channel_toko'] ?? ($firstItem['channel_toko'] ?? 'Specialist Traditional Store (SSO)');
                    } elseif ($fn === 'oos_items_json') {
                        $vJson = $oosItems;
                        $vText = json_encode($oosItems, JSON_UNESCAPED_UNICODE);
                        $vNum = count($oosItems);
                    } elseif (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
                        $saved = $allSavedMedia[$field->id] ?? $allSavedMedia[$field->field_name] ?? [];
                        if (!empty($saved)) {
                            $photoPath = $saved[0];
                            $vJson = $saved;
                            $vText = implode(', ', $saved);
                        }
                    } else {
                        $raw = $normalizedValues[$fn] ?? null;
                        $vText = is_string($raw) ? $raw : ($raw !== null ? json_encode($raw) : null);
                        if (is_numeric($raw)) $vNum = (float)$raw;
                    }

                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => $field->id,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $vText,
                        'value_number' => $vNum,
                        'value_json' => $vJson,
                        'media_url' => $photoPath,
                    ]);
                }

                $hasOosJson = ReportSubmissionValue::where('report_submission_id', $sub->id)
                    ->where('field_name', 'oos_items_json')
                    ->exists();
                if (!$hasOosJson) {
                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => null,
                        'field_name' => 'oos_items_json',
                        'field_type' => 'json',
                        'value_text' => json_encode($oosItems, JSON_UNESCAPED_UNICODE),
                        'value_number' => count($oosItems),
                        'value_json' => $oosItems,
                        'media_url' => null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Out of Stock (' . count($oosItems) . ' produk) berhasil dikirim.',
                    'data' => [
                        'id' => $sub->id,
                        'submission_code' => $sub->submission_code,
                        'template_title' => $template->title,
                        'submitted_at' => $sub->submitted_at->toDateTimeString(),
                        'status' => $sub->status,
                    ],
                ]);
            }

            // Cek apakah ini template Stock End dengan cart multi-produk
            $stockEndItems = [];
            if (isset($valuesInput['stock_items_json'])) {
                $rawStockItems = $valuesInput['stock_items_json'];
                if (is_string($rawStockItems)) {
                    $rawStockItems = json_decode($rawStockItems, true) ?? [];
                }
                if (is_array($rawStockItems)) {
                    $stockEndItems = $rawStockItems;
                }
            }
            if (empty($stockEndItems) && isset($normalizedValues['stock_items_json'])) {
                $rawStockItems = $normalizedValues['stock_items_json'];
                if (is_string($rawStockItems)) {
                    $rawStockItems = json_decode($rawStockItems, true) ?? [];
                }
                if (is_array($rawStockItems)) {
                    $stockEndItems = $rawStockItems;
                }
            }

            $isStockEndTemplate = ($template->code === 'RPT-DULUX-STOCK-END' || Str::contains($template->code, 'STOCK-END'));
            $isStockEndWithItems = $isStockEndTemplate && !empty($stockEndItems) && count($stockEndItems) > 0;

            // JIKA STOCK END DENGAN MULTI-PRODUK: BUAT 1 BARIS REPORT SUBMISSION DENGAN DETAIL RINCIAN STOK
            if ($isStockEndWithItems) {
                // Simpan seluruh file media/foto sekali untuk submission ini
                $allSavedMedia = [];
                foreach ($template->fields as $field) {
                    if (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature', 'image'])) {
                        $savedPhotos = $this->saveUploadedPhotos($request, 0, (string)$field->id, $field->field_name);
                        if (!empty($savedPhotos)) {
                            $allSavedMedia[$field->id] = $savedPhotos;
                            $allSavedMedia[$field->field_name] = $savedPhotos;
                        }
                    }
                }

                $sub = ReportSubmission::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $principalId,
                    'employee_id' => $employee->id,
                    'work_location_id' => $workLocationId,
                    'itinerary_item_id' => $itineraryItemId,
                    'submission_code' => $submissionCode,
                    'store_name' => $storeName,
                    'address' => $address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_within_radius' => $isWithinRadius,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                $allProductNames = [];
                $allBrands = [];
                $grandQtyGalon = 0;
                $grandQtyPail = 0;
                $grandQtyTinter = 0;
                $grandTotalLiter = 0.0;

                foreach ($stockEndItems as $item) {
                    $pName = trim((string)($item['product_name'] ?? ($item['produk'] ?? '')));
                    if (!empty($pName) && !in_array($pName, $allProductNames)) {
                        $allProductNames[] = $pName;
                    }
                    $bName = trim((string)($item['brand'] ?? ''));
                    if (!empty($bName) && !in_array($bName, $allBrands)) {
                        $allBrands[] = $bName;
                    }

                    $qGalon = intval($item['qty_galon'] ?? ($item['kuantiti_galon'] ?? 0));
                    $qPail = intval($item['qty_pail'] ?? ($item['kuantiti_pail'] ?? 0));
                    $qTinter = intval($item['qty_kaleng_tinta'] ?? 0);
                    $vLit = floatval($item['volume_liter'] ?? 0.0);

                    $grandQtyGalon += $qGalon;
                    $grandQtyPail += $qPail;
                    $grandQtyTinter += $qTinter;
                    $grandTotalLiter += $vLit;
                }

                $firstItem = $stockEndItems[0];
                $productSummary = implode(', ', $allProductNames);
                $brandSummary = count($allBrands) > 0 ? implode(', ', $allBrands) : ($firstItem['brand'] ?? 'Dulux');

                foreach ($template->fields as $field) {
                    $fn = strtolower(trim($field->field_name));
                    $vText = null;
                    $vNum = null;
                    $vJson = null;
                    $photoPath = null;

                    if ($fn === 'produk' || $fn === 'nama_produk' || $fn === 'produk_stock_end' || str_contains($fn, 'produk')) {
                        $vText = $productSummary;
                    } elseif ($fn === 'brand' || $fn === 'brand_cat' || str_contains($fn, 'brand')) {
                        $vText = $brandSummary;
                    } elseif ($fn === 'volume_liter' || $fn === 'total_volume_stok_liter' || $fn === 'estimasi_total_volume_stok_di_toko_liter' || str_contains($fn, 'volume')) {
                        $vNum = round($grandTotalLiter, 2);
                        $vText = (string)round($grandTotalLiter, 2);
                    } elseif ($fn === 'kuantiti_galon' || $fn === 'stok_qty_galon' || $fn === 'stok_fisik_kemasan_galon_qty' || $fn === 'qty_galon' || str_contains($fn, 'galon')) {
                        $vNum = (float)$grandQtyGalon;
                        $vText = (string)$grandQtyGalon;
                    } elseif ($fn === 'kuantiti_pail' || $fn === 'stok_qty_pail' || $fn === 'stok_fisik_kemasan_pail_qty' || $fn === 'qty_pail' || str_contains($fn, 'pail')) {
                        $vNum = (float)$grandQtyPail;
                        $vText = (string)$grandQtyPail;
                    } elseif ($fn === 'qty_kaleng_tinta' || $fn === 'kuantiti_jumlah_kaleng_tinta_tinter' || $fn === 'kuantiti_kaleng_tinta' || str_contains($fn, 'kaleng_tinta')) {
                        $vNum = (float)$grandQtyTinter;
                        $vText = (string)$grandQtyTinter;
                    } elseif ($fn === 'kategori_tinter' || $fn === 'kategori_tinter_mesin_tinting') {
                        $vText = $firstItem['kategori_tinter'] ?? (!empty($firstItem['is_tinter']) ? 'Tinting' : 'Non-Tinting');
                    } elseif ($fn === 'tipe_tinter_warna' || $fn === 'tipe_tinter_warna_pasta_pewarna') {
                        $vText = $firstItem['tipe_tinter_warna'] ?? '-';
                    } elseif ($fn === 'tanggal_pencatatan_stok') {
                        $vText = $normalizedValues['tanggal_pencatatan_stok'] ?? now()->toDateString();
                    } elseif ($fn === 'keterangan_akses' || $fn === 'status_akses_gudang' || $fn === 'status_akses_pengecekan_gudang_toko') {
                        $vText = $normalizedValues['status_akses_gudang'] ?? ($normalizedValues['keterangan_akses'] ?? 'Full Access (Bisa Cek Rak & Gudang Toko Bebas)');
                    } elseif ($fn === 'warna' || $fn === 'base_warna' || $fn === 'base_tipe_warna' || str_contains($fn, 'base')) {
                        $vText = count($stockEndItems) > 1 ? 'Multi-Base (' . count($stockEndItems) . ' Item)' : ($firstItem['warna'] ?? 'ALL');
                    } elseif ($fn === 'conf') {
                        $vNum = isset($firstItem['conf']) ? floatval($firstItem['conf']) : 1.27;
                        $vText = (string)($firstItem['conf'] ?? '1.27');
                    } elseif ($fn === 'catatan' || $fn === 'keterangan_stok_toko' || $fn === 'keterangan_kendala_stok_tinter_toko') {
                        $vText = $normalizedValues['catatan'] ?? ($normalizedValues['keterangan_stok_toko'] ?? ($normalizedValues['keterangan_kendala_stok_tinter_toko'] ?? ''));
                    } elseif ($fn === 'stock_items_json') {
                        $vJson = $stockEndItems;
                        $vText = json_encode($stockEndItems, JSON_UNESCAPED_UNICODE);
                        $vNum = count($stockEndItems);
                    } elseif (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature', 'image'])) {
                        $saved = $allSavedMedia[$field->id] ?? $allSavedMedia[$field->field_name] ?? [];
                        if (!empty($saved)) {
                            $photoPath = $saved[0];
                            $vJson = $saved;
                            $vText = $saved[0];
                        }
                    } else {
                        $fKey = (string)$field->id;
                        $raw = $normalizedValues[$fKey] ?? $normalizedValues[$fn] ?? null;
                        if ($raw !== null) {
                            $vText = is_string($raw) ? $raw : json_encode($raw);
                            if (is_numeric($raw)) $vNum = (float)$raw;
                        }
                    }

                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => $field->id,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $vText,
                        'value_number' => $vNum,
                        'value_json' => $vJson,
                        'media_url' => $photoPath,
                    ]);
                }

                // Pastikan stock_items_json tersimpan
                $hasStockJson = ReportSubmissionValue::where('report_submission_id', $sub->id)
                    ->where('field_name', 'stock_items_json')
                    ->exists();
                if (!$hasStockJson) {
                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => null,
                        'field_name' => 'stock_items_json',
                        'field_type' => 'json',
                        'value_text' => json_encode($stockEndItems, JSON_UNESCAPED_UNICODE),
                        'value_number' => count($stockEndItems),
                        'value_json' => $stockEndItems,
                        'media_url' => null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Stock End (' . count($stockEndItems) . ' produk) berhasil dikirim.',
                    'data' => [
                        'id' => $sub->id,
                        'submission_code' => $sub->submission_code,
                        'template_title' => $template->title,
                        'submitted_at' => $sub->submitted_at->toDateTimeString(),
                        'status' => $sub->status,
                    ],
                ]);
            }

            // Cek apakah ini template Wings MBR Sales dengan cart multi-produk
            $mbrSalesItems = [];
            if (isset($valuesInput['mbr_sales_items_json'])) {
                $rawMbrItems = $valuesInput['mbr_sales_items_json'];
                if (is_string($rawMbrItems)) {
                    $rawMbrItems = json_decode($rawMbrItems, true) ?? [];
                }
                if (is_array($rawMbrItems)) {
                    $mbrSalesItems = $rawMbrItems;
                }
            }
            if (empty($mbrSalesItems) && isset($normalizedValues['mbr_sales_items_json'])) {
                $rawMbrItems = $normalizedValues['mbr_sales_items_json'];
                if (is_string($rawMbrItems)) {
                    $rawMbrItems = json_decode($rawMbrItems, true) ?? [];
                }
                if (is_array($rawMbrItems)) {
                    $mbrSalesItems = $rawMbrItems;
                }
            }

            $isWingsMbrSalesTemplate = ($template->code === 'RPT-WINGS-MBR-SALES-01' || Str::contains($template->code, 'MBR-SALES') || (stripos($template->title, 'mbr') !== false && stripos($template->title, 'penjualan') !== false));
            $isMbrSalesWithItems = $isWingsMbrSalesTemplate && !empty($mbrSalesItems);

            // JIKA WINGS MBR SALES DENGAN MULTI-PRODUK CART: BUAT 1 BARIS REPORT SUBMISSION
            if ($isMbrSalesWithItems) {
                // Simpan seluruh file media/foto (termasuk foto_sell_out_toko)
                $allSavedMedia = [];
                foreach ($template->fields as $field) {
                    if (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature', 'image'])) {
                        $savedPhotos = $this->saveUploadedPhotos($request, 0, (string)$field->id, $field->field_name);
                        if (!empty($savedPhotos)) {
                            $allSavedMedia[$field->id] = $savedPhotos;
                            $allSavedMedia[$field->field_name] = $savedPhotos;
                        }
                    }
                }

                // Simpan foto struk per item produk jika di-upload
                foreach ($mbrSalesItems as $idx => &$mItem) {
                    $uploadedStruk = null;
                    $candidateKeys = [
                        "struk_photo_{$idx}",
                        "photo_struk_{$idx}",
                        "photo_struk_photo_{$idx}",
                        "photo_photo_struk_{$idx}",
                        "struk_{$idx}",
                        "photo_struk{$idx}",
                        "photo_receipt_{$idx}",
                    ];
                    foreach ($candidateKeys as $ck) {
                        if ($request->hasFile($ck)) {
                            $uploadedStruk = $request->file($ck);
                            break;
                        }
                    }

                    // Fallback: cari dari allFiles() jika ada key yang mengandung 'struk' dan indexnya
                    if (!$uploadedStruk) {
                        foreach ($request->allFiles() as $fKey => $fVal) {
                            $lowerK = strtolower($fKey);
                            if (str_contains($lowerK, 'struk') && str_contains($lowerK, (string)$idx)) {
                                $uploadedStruk = $fVal;
                                break;
                            }
                        }
                    }

                    if ($uploadedStruk && $uploadedStruk->isValid()) {
                        $filename = "struk_mbr_{$idx}_" . time() . '_' . uniqid() . '.' . $uploadedStruk->getClientOriginalExtension();
                        $path = $uploadedStruk->storeAs("reports/" . now()->format('Y-m'), $filename, 'public');
                        if ($path) {
                            $mItem['struk_photo_url'] = $path;
                            $mItem['photo_struk_url'] = $path;
                            $mItem['struk_photo_path'] = $path;
                            $mItem['foto_struk'] = $path;
                        }
                    }
                }
                unset($mItem);

                // Buat ReportSubmission tunggal
                $sub = ReportSubmission::create([
                    'report_template_id' => $template->id,
                    'principal_id' => $principalId,
                    'employee_id' => $employee->id,
                    'work_location_id' => $workLocationId,
                    'itinerary_item_id' => $itineraryItemId,
                    'submission_code' => $submissionCode,
                    'store_name' => $storeName,
                    'address' => $address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_within_radius' => $isWithinRadius,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                // Hitung total akumulatif
                $totalQty = 0;
                $totalValueRp = 0;
                $totalBoothRp = 0;
                $totalKasirRp = 0;

                foreach ($mbrSalesItems as $item) {
                    $q = intval($item['qty'] ?? 0);
                    $val = floatval($item['value_rp'] ?? ($q * floatval($item['store_price'] ?? 0)));
                    $payType = strtolower($item['payment_type'] ?? 'booth');

                    $totalQty += $q;
                    $totalValueRp += $val;
                    if (str_contains($payType, 'kasir')) {
                        $totalKasirRp += $val;
                    } else {
                        $totalBoothRp += $val;
                    }
                }

                foreach ($template->fields as $field) {
                    $fn = strtolower(trim($field->field_name));
                    $vText = null;
                    $vNum = null;
                    $vJson = null;
                    $photoPath = null;

                    if ($fn === 'mbr_sales_items_json') {
                        $vJson = $mbrSalesItems;
                        $vText = json_encode($mbrSalesItems, JSON_UNESCAPED_UNICODE);
                        $vNum = count($mbrSalesItems);
                    } elseif ($fn === 'total_qty_penjualan') {
                        $vNum = $totalQty;
                        $vText = (string) $totalQty;
                    } elseif ($fn === 'total_value_penjualan_rp') {
                        $vNum = $totalValueRp;
                        $vText = 'Rp ' . number_format($totalValueRp, 0, ',', '.');
                    } elseif ($fn === 'total_bayar_di_booth_rp') {
                        $vNum = $totalBoothRp;
                        $vText = 'Rp ' . number_format($totalBoothRp, 0, ',', '.');
                    } elseif ($fn === 'total_bayar_di_kasir_rp') {
                        $vNum = $totalKasirRp;
                        $vText = 'Rp ' . number_format($totalKasirRp, 0, ',', '.');
                    } elseif (in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature', 'image'])) {
                        $saved = $allSavedMedia[$field->id] ?? $allSavedMedia[$field->field_name] ?? [];
                        if (!empty($saved)) {
                            $photoPath = $saved[0];
                            $vJson = $saved;
                            $vText = $saved[0];
                        }
                    } else {
                        $fKey = (string)$field->id;
                        $raw = $normalizedValues[$fKey] ?? $normalizedValues[$fn] ?? null;
                        if ($raw !== null) {
                            $vText = is_string($raw) ? $raw : json_encode($raw);
                            if (is_numeric($raw)) $vNum = (float)$raw;
                        }
                    }

                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => $field->id,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $vText,
                        'value_number' => $vNum,
                        'value_json' => $vJson,
                        'media_url' => $photoPath,
                    ]);
                }

                // Pastikan mbr_sales_items_json tersimpan
                $hasMbrJson = ReportSubmissionValue::where('report_submission_id', $sub->id)
                    ->where('field_name', 'mbr_sales_items_json')
                    ->exists();
                if (!$hasMbrJson) {
                    ReportSubmissionValue::create([
                        'report_submission_id' => $sub->id,
                        'report_form_field_id' => null,
                        'field_name' => 'mbr_sales_items_json',
                        'field_type' => 'json',
                        'value_text' => json_encode($mbrSalesItems, JSON_UNESCAPED_UNICODE),
                        'value_number' => count($mbrSalesItems),
                        'value_json' => $mbrSalesItems,
                        'media_url' => null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Penjualan Event MBR (' . count($mbrSalesItems) . ' produk) berhasil dikirim.',
                    'data' => [
                        'id' => $sub->id,
                        'submission_code' => $sub->submission_code,
                        'template_title' => $template->title,
                        'submitted_at' => $sub->submitted_at->toDateTimeString(),
                        'status' => $sub->status,
                    ],
                ]);
            }

            // STANDAR / SINGLE SUBMISSION (Untuk template selain Offtake/OOS/Stock/MBR multi-produk)
            $submission = ReportSubmission::create([
                'report_template_id' => $template->id,
                'principal_id' => $principalId,
                'employee_id' => $employee->id,
                'work_location_id' => $workLocationId,
                'itinerary_item_id' => $itineraryItemId,
                'submission_code' => $submissionCode,
                'store_name' => $storeName,
                'address' => $address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_within_radius' => $isWithinRadius,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Simpan setiap parameter input
            foreach ($template->fields as $fieldIndex => $field) {
                $fieldId = (string) $field->id;
                $fieldName = $field->field_name;
                $fieldLabel = $field->field_label;
                
                // 1. Cari value langsung dari ID atau field_name
                $rawValue = $valuesInput[$fieldId] ?? $valuesInput[$fieldName] ?? null;

                // 2. Cari dari varian slug dan lowercase nama field / label
                if ($rawValue === null) {
                    $slugName = strtolower(str_replace([' ', '-'], '_', (string)$fieldName));
                    $slugLabel = $fieldLabel ? strtolower(str_replace([' ', '-'], '_', (string)$fieldLabel)) : null;
                    $rawValue = $normalizedValues[$slugName] ?? ($slugLabel ? ($normalizedValues[$slugLabel] ?? null) : null);
                }

                // 3. Cari dari request input langsung (form multipart)
                if ($rawValue === null) {
                    $rawValue = $request->input("val_{$fieldId}") 
                        ?? $request->input("val_{$fieldName}") 
                        ?? $request->input($fieldName) 
                        ?? $request->input($fieldId) 
                        ?? null;
                }

                // 4. Fallback jika mobile app mengirimkan array berindeks urut
                if ($rawValue === null && array_is_list($valuesInput) && isset($valuesInput[$fieldIndex])) {
                    $rawValue = $valuesInput[$fieldIndex];
                }
                
                $valueText = null;
                $valueNumber = null;
                $valueJson = null;
                $photoPath = null;

                $isMediaField = in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature']);

                // Handle file upload (single foto, multi-foto, atau tanda tangan)
                $savedPhotos = $this->saveUploadedPhotos($request, $submission->id, $fieldId, $fieldName);
                if (!empty($savedPhotos)) {
                    $photoPath = $savedPhotos[0];
                    $valueJson = $savedPhotos;
                    $valueText = implode(', ', $savedPhotos);
                }

                // Handle format data berdasarkan field_type (HANYA untuk non-media)
                if (!$isMediaField) {
                    if ($field->field_type === 'currency') {
                        if (is_numeric($rawValue)) {
                            $valueNumber = (float) $rawValue;
                        } elseif (is_string($rawValue)) {
                            // Bersihkan semua karakter non-digit untuk currency Rupiah ("Rp 195.000" -> 195000)
                            $cleanNum = preg_replace('/[^0-9]/', '', $rawValue);
                            $valueNumber = $cleanNum !== '' ? (float) $cleanNum : null;
                        }
                        $valueText = $rawValue !== null ? (string) $rawValue : null;
                    } elseif (in_array($field->field_type, ['number', 'integer', 'percentage', 'rating', 'rating_star', 'slider'])) {
                        if (is_numeric($rawValue)) {
                            $valueNumber = (float) $rawValue;
                        } elseif (is_string($rawValue)) {
                            // Normalisasi koma/titik desimal
                            $standardized = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $rawValue));
                            if (substr_count($standardized, '.') > 1) {
                                $standardized = str_replace('.', '', $standardized);
                            }
                            $valueNumber = is_numeric($standardized) ? (float) $standardized : null;
                        }
                        $valueText = $rawValue !== null ? (string) $rawValue : null;
                    } elseif (in_array($field->field_type, ['multi_select', 'checkbox_group', 'sku_list']) || is_array($rawValue)) {
                        $valueJson = is_array($rawValue) ? $rawValue : [$rawValue];
                        $valueText = is_array($rawValue) ? implode(', ', $rawValue) : (string) $rawValue;
                    } else {
                        $valueText = is_string($rawValue) ? $rawValue : ($rawValue !== null ? json_encode($rawValue) : null);
                    }
                }

                // Jika berupa media / foto dan belum ada valueText
                if ($photoPath && empty($valueText)) {
                    $valueText = $photoPath;
                }

                ReportSubmissionValue::create([
                    'report_submission_id' => $submission->id,
                    'report_form_field_id' => $field->id,
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    'value_text' => $valueText,
                    'value_number' => $valueNumber,
                    'value_json' => $valueJson,
                    'media_url' => $photoPath,
                ]);
            }

            // Simpan data_kompetitor_list jika dikirimkan dari formulir dinamis CBP
            if (isset($valuesInput['data_kompetitor_list'])) {
                $compListRaw = $valuesInput['data_kompetitor_list'];
                if (is_string($compListRaw)) {
                    $compListRaw = json_decode($compListRaw, true) ?? [];
                }
                if (is_array($compListRaw) && !empty($compListRaw)) {
                    ReportSubmissionValue::create([
                        'report_submission_id' => $submission->id,
                        'report_form_field_id' => null,
                        'field_name' => 'data_kompetitor_list',
                        'field_type' => 'json',
                        'value_text' => json_encode($compListRaw),
                        'value_number' => count($compListRaw),
                        'value_json' => $compListRaw,
                    ]);
                }
            }

            // Simpan offtake_items_json jika dikirimkan dari formulir dinamis Offtake
            if (isset($valuesInput['offtake_items_json'])) {
                $offtakeItemsRaw = $valuesInput['offtake_items_json'];
                if (is_string($offtakeItemsRaw)) {
                    $offtakeItemsRaw = json_decode($offtakeItemsRaw, true) ?? [];
                }
                if (is_array($offtakeItemsRaw) && !empty($offtakeItemsRaw)) {
                    ReportSubmissionValue::updateOrCreate(
                        [
                            'report_submission_id' => $submission->id,
                            'field_name' => 'offtake_items_json',
                        ],
                        [
                            'report_form_field_id' => $template->fields->where('field_name', 'offtake_items_json')->first()?->id,
                            'field_type' => 'textarea',
                            'value_text' => json_encode($offtakeItemsRaw),
                            'value_number' => count($offtakeItemsRaw),
                            'value_json' => $offtakeItemsRaw,
                        ]
                    );
                }
            }

            // Khusus Laporan Daily Maintenance Dulux: Simpan nomor seri & tipe mesin ke Master Toko (WorkLocation)
            if ($template->code === 'RPT-DULUX-DAILY-MAINTENANCE' && $submission->work_location_id) {
                try {
                    $workLoc = \App\Models\WorkLocation::find($submission->work_location_id);
                    if ($workLoc) {
                        $tipeMesinVal = $submission->values()->where('field_name', 'tipe_mesin_post')->value('value_text');
                        $noMesinVal = $submission->values()->where('field_name', 'no_mesin_post')->value('value_text');

                        $updateData = [];

                        // Update atau tambahkan ke daftar array machines
                        if (!empty($tipeMesinVal) && stripos($tipeMesinVal, 'tidak memiliki') === false) {
                            $existingMachines = is_array($workLoc->machines) ? $workLoc->machines : [];

                            // Jika $existingMachines kosong namun $workLoc memiliki machine_type lama yang berbeda, pertahankan!
                            if (empty($existingMachines) && !empty($workLoc->machine_type) && strcasecmp(trim($workLoc->machine_type), trim($tipeMesinVal)) !== 0) {
                                $existingMachines[] = [
                                    'machine_type' => trim($workLoc->machine_type),
                                    'machine_serial_no' => trim($workLoc->machine_serial_no ?? ''),
                                ];
                            }

                            // Khusus Toko Demo Arina Rajawali: pastikan kedua demo mesin selalu ada
                            if (stripos($workLoc->name, 'Rajawali') !== false || stripos($workLoc->name, 'Arina Rajawali') !== false) {
                                $hasType1 = false;
                                $hasType2 = false;
                                foreach ($existingMachines as $m) {
                                    if (strcasecmp(trim($m['machine_type'] ?? ''), 'Type Mesin 1') === 0) $hasType1 = true;
                                    if (strcasecmp(trim($m['machine_type'] ?? ''), 'Type Mesin 2') === 0) $hasType2 = true;
                                }
                                if (!$hasType1) {
                                    $existingMachines[] = ['machine_type' => 'Type Mesin 1', 'machine_serial_no' => 'XX-001'];
                                }
                                if (!$hasType2) {
                                    $existingMachines[] = ['machine_type' => 'Type Mesin 2', 'machine_serial_no' => 'XX-002'];
                                }
                            }

                            $found = false;
                            foreach ($existingMachines as &$em) {
                                if (strcasecmp(trim($em['machine_type'] ?? ''), trim($tipeMesinVal)) === 0) {
                                    if (!empty($noMesinVal)) {
                                        $em['machine_serial_no'] = $noMesinVal;
                                    }
                                    $found = true;
                                    break;
                                }
                            }
                            unset($em);
                            if (!$found) {
                                $existingMachines[] = [
                                    'machine_type' => $tipeMesinVal,
                                    'machine_serial_no' => $noMesinVal ?: '',
                                ];
                            }
                            $updateData['machines'] = $existingMachines;
                            if (empty($workLoc->machine_type)) {
                                $updateData['machine_type'] = $tipeMesinVal;
                            }
                            if (empty($workLoc->machine_serial_no) && !empty($noMesinVal)) {
                                $updateData['machine_serial_no'] = $noMesinVal;
                            }
                        }

                        if (!empty($updateData)) {
                            $workLoc->update($updateData);
                        }
                    }
                } catch (\Throwable $e) {}
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil dikirim dan tersimpan di sistem.',
                'data' => [
                    'id' => $submission->id,
                    'submission_code' => $submission->submission_code,
                    'template_title' => $template->title,
                    'submitted_at' => $submission->submitted_at->toDateTimeString(),
                    'status' => $submission->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan laporan: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Get active work locations/stores for reporting, filtered by employee's principal.
     */
    public function stores(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $employee->load(['branch', 'principal', 'company']);
        $employeeArea = $employee->branch?->name ?? ($employee->area ?: null);
        $today = \Carbon\Carbon::today('Asia/Jakarta')->toDateString();

        // Cari ID lokasi yang ada di itinerary hari ini
        $itineraryLocationIds = [];
        $itinerary = \App\Models\Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items'])
            ->first();

        if ($itinerary) {
            $itineraryLocationIds = $itinerary->items->pluck('work_location_id')->map(fn($id) => (int)$id)->toArray();
        }

        // Ambil semua work location aktif (sama seperti form visit / availableWorkLocations)
        $employee->loadMissing('position');
        $locations = \App\Models\WorkLocation::with(['branch', 'principal', 'company'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($loc) use ($itineraryLocationIds, $employee) {
                $data = $loc->toArray();
                $areaName = $loc->branch ? $loc->branch->name : ($loc->area ?: ($loc->region ?: 'Lainnya'));
                $data['area'] = $areaName;
                $data['is_today_itinerary'] = in_array((int)$loc->id, $itineraryLocationIds);
                $data['radius_meter'] = $loc->getEffectiveRadiusForEmployee($employee);

                // Format & lengkapi daftar mesin untuk Laporan Daily Maintenance
                $machinesList = [];
                if (!empty($loc->machines) && is_array($loc->machines)) {
                    foreach ($loc->machines as $m) {
                        if (is_array($m)) {
                            $mType = trim($m['machine_type'] ?? $m['type'] ?? '');
                            $mSerial = trim($m['machine_serial_no'] ?? $m['serial_no'] ?? '');
                            if ($mType || $mSerial) {
                                $machinesList[] = [
                                    'machine_type' => $mType ?: 'Mesin Tinting',
                                    'machine_serial_no' => $mSerial,
                                ];
                            }
                        }
                    }
                }
                if (empty($machinesList) && (!empty($loc->machine_type) || !empty($loc->machine_serial_no))) {
                    $machinesList[] = [
                        'machine_type' => trim((string)$loc->machine_type) ?: 'Mesin Tinting',
                        'machine_serial_no' => trim((string)$loc->machine_serial_no),
                    ];
                }

                // Default mesin untuk Toko Demo Arina Rajawali jika belum lengkap 2 mesin
                if ((empty($machinesList) || count($machinesList) < 2) && (stripos($loc->name, 'Rajawali') !== false || stripos($loc->name, 'Arina Rajawali') !== false)) {
                    $machinesList = [
                        ['machine_type' => 'Type Mesin 1', 'machine_serial_no' => 'XX-001'],
                        ['machine_type' => 'Type Mesin 2', 'machine_serial_no' => 'XX-002'],
                    ];
                    $data['machine_type'] = $machinesList[0]['machine_type'];
                    $data['machine_serial_no'] = $machinesList[0]['machine_serial_no'];
                }

                // Default mesin untuk Toko Demo Kalilor jika belum diset
                if (empty($machinesList) && stripos($loc->name, 'Kalilor') !== false) {
                    $machinesList = [
                        ['machine_type' => 'Mesin D200 (Automatic Tinting)', 'machine_serial_no' => 'POST-2022-SUB-042'],
                        ['machine_type' => 'Mesin Discovery (Automatic Tinting)', 'machine_serial_no' => 'POST-2023-SUB-089'],
                    ];
                    $data['machine_type'] = $machinesList[0]['machine_type'];
                    $data['machine_serial_no'] = $machinesList[0]['machine_serial_no'];
                }

                $data['machines'] = $machinesList;
                return $data;
            });

        return response()->json([
            'status' => 'success',
            'default_area' => $employeeArea,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'area' => $employeeArea,
                'principal_id' => $employee->principal_id,
                'branch_id' => $employee->branch_id,
                'company_id' => $employee->company_id,
            ],
            'count' => $locations->count(),
            'data' => $locations->values(),
        ]);
    }

    /**
     * Get submission history for the authenticated employee.
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $limit = $request->integer('limit', 50);
        $submissions = ReportSubmission::with([
            'template' => function ($q) {
                $q->with(['fields' => fn($fq) => $fq->orderBy('order_index', 'asc'), 'products']);
            },
            'principal',
            'workLocation',
            'values.formField',
        ])
            ->where('employee_id', $employee->id)
            ->orderBy('submitted_at', 'desc')
            ->paginate($limit);

        $items = collect($submissions->items())->map(function ($s) {
            $isApproved = in_array(strtolower($s->status ?? ''), ['approved', 'verified']);
            $canEdit = !$isApproved;

            $valuesFormatted = $s->values->map(function ($v) {
                $mediaFullUrls = $this->formatMediaFullUrls($v);
                $mediaFullUrl = $mediaFullUrls[0] ?? null;

                return [
                    'id' => $v->id,
                    'report_form_field_id' => $v->report_form_field_id,
                    'field_name' => $v->field_name,
                    'field_label' => $v->formField?->field_label ?? Str::title(str_replace('_', ' ', $v->field_name)),
                    'field_type' => $v->field_type,
                    'value_text' => $v->value_text,
                    'value_number' => $v->value_number !== null ? (float) $v->value_number : null,
                    'value_json' => $v->value_json,
                    'media_url' => $v->media_url,
                    'media_full_url' => $mediaFullUrl,
                    'media_full_urls' => $mediaFullUrls,
                ];
            });

            return [
                'id' => $s->id,
                'submission_code' => $s->submission_code,
                'report_template_id' => $s->report_template_id,
                'template_title' => $s->template?->title ?? 'Laporan',
                'template_code' => $s->template?->code ?? '',
                'template_category' => $s->template?->category ?? 'general',
                'store_name' => $s->store_name,
                'address' => $s->address,
                'work_location_id' => $s->work_location_id,
                'status' => $s->status ?? 'pending',
                'status_label' => match(strtolower($s->status ?? '')) {
                    'approved', 'verified' => 'Terverifikasi (Approve)',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Verifikasi (Pending)',
                },
                'can_edit' => $canEdit,
                'is_within_radius' => (bool) $s->is_within_radius,
                'latitude' => $s->latitude ? (float) $s->latitude : null,
                'longitude' => $s->longitude ? (float) $s->longitude : null,
                'submitted_at' => $s->submitted_at ? $s->submitted_at->toDateTimeString() : null,
                'submitted_at_formatted' => $s->submitted_at ? $s->submitted_at->format('d M Y, H:i') : null,
                'principal_name' => $s->principal?->name,
                'template' => $s->template ? [
                    'id' => $s->template->id,
                    'code' => $s->template->code,
                    'title' => $s->template->title,
                    'description' => $s->template->description,
                    'category' => $s->template->category,
                    'color' => $s->template->color ?? '#0F52BA',
                    'icon' => $s->template->icon ?? 'document-text',
                    'fields' => $s->template->fields->map(fn($f) => [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type === 'product_select' ? 'dropdown' : $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'options' => $f->options ?? [],
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                    ]),
                    'products' => $s->template->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'brand' => $p->brand,
                        'price' => (float) $p->price,
                        'formatted_price' => $p->formatted_price,
                    ]),
                ] : null,
                'values' => $valuesFormatted,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'current_page' => $submissions->currentPage(),
            'total' => $submissions->total(),
            'last_page' => $submissions->lastPage(),
        ]);
    }

    /**
     * Get single report submission detail.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $submission = ReportSubmission::with([
            'template' => function ($q) {
                $q->with(['fields' => fn($fq) => $fq->orderBy('order_index', 'asc'), 'products']);
            },
            'principal',
            'workLocation',
            'values.formField',
        ])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $isApproved = in_array(strtolower($submission->status ?? ''), ['approved', 'verified']);
        $canEdit = !$isApproved;

        $valuesFormatted = $submission->values->map(function ($v) {
            $mediaFullUrls = $this->formatMediaFullUrls($v);
            $mediaFullUrl = $mediaFullUrls[0] ?? null;

            return [
                'id' => $v->id,
                'report_form_field_id' => $v->report_form_field_id,
                'field_name' => $v->field_name,
                'field_label' => $v->formField?->field_label ?? Str::title(str_replace('_', ' ', $v->field_name)),
                'field_type' => $v->field_type,
                'value_text' => $v->value_text,
                'value_number' => $v->value_number !== null ? (float) $v->value_number : null,
                'value_json' => $v->value_json,
                'media_url' => $v->media_url,
                'media_full_url' => $mediaFullUrl,
                'media_full_urls' => $mediaFullUrls,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $submission->id,
                'submission_code' => $submission->submission_code,
                'report_template_id' => $submission->report_template_id,
                'template_title' => $submission->template?->title ?? 'Laporan',
                'template_code' => $submission->template?->code ?? '',
                'template_category' => $submission->template?->category ?? 'general',
                'store_name' => $submission->store_name,
                'address' => $submission->address,
                'work_location_id' => $submission->work_location_id,
                'status' => $submission->status ?? 'pending',
                'status_label' => match(strtolower($submission->status ?? '')) {
                    'approved', 'verified' => 'Terverifikasi (Approve)',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Verifikasi (Pending)',
                },
                'can_edit' => $canEdit,
                'is_within_radius' => (bool) $submission->is_within_radius,
                'latitude' => $submission->latitude ? (float) $submission->latitude : null,
                'longitude' => $submission->longitude ? (float) $submission->longitude : null,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                'submitted_at_formatted' => $submission->submitted_at ? $submission->submitted_at->format('d M Y, H:i') : null,
                'principal_name' => $submission->principal?->name,
                'template' => $submission->template ? [
                    'id' => $submission->template->id,
                    'code' => $submission->template->code,
                    'title' => $submission->template->title,
                    'description' => $submission->template->description,
                    'category' => $submission->template->category,
                    'color' => $submission->template->color ?? '#0F52BA',
                    'icon' => $submission->template->icon ?? 'document-text',
                    'fields' => $submission->template->fields->map(fn($f) => [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type === 'product_select' ? 'dropdown' : $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'options' => $f->options ?? [],
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                    ]),
                    'products' => $submission->template->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'brand' => $p->brand,
                        'price' => (float) $p->price,
                        'formatted_price' => $p->formatted_price,
                    ]),
                ] : null,
                'values' => $valuesFormatted,
            ],
        ]);
    }

    /**
     * Update an existing report submission if not yet approved.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $submission = ReportSubmission::with(['template.fields', 'values'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // Cek apakah status sudah Approve / Verified
        if (in_array(strtolower($submission->status ?? ''), ['approved', 'verified'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Laporan ini sudah disetujui (Approved) dan tidak dapat diubah lagi.',
            ], 422);
        }

        $template = $submission->template;
        if (!$template) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template form pelaporan tidak ditemukan.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Update data header jika dikirimkan
            if ($request->filled('store_name')) {
                $submission->store_name = $request->store_name;
            }
            if ($request->filled('address')) {
                $submission->address = $request->address;
            }
            if ($request->filled('work_location_id')) {
                $submission->work_location_id = $request->work_location_id;
            }
            $submission->updated_at = now();
            $submission->save();

            // Decode input values
            $valuesInput = $request->input('values');
            if (is_string($valuesInput)) {
                $valuesInput = json_decode($valuesInput, true) ?? [];
            }
            if (!is_array($valuesInput)) {
                $valuesInput = [];
            }

            // Update setiap field parameter
            foreach ($template->fields as $field) {
                $fieldId = (string) $field->id;
                $fieldName = $field->field_name;

                $existingVal = $submission->values->first(function ($val) use ($field, $fieldId, $fieldName) {
                    return $val->report_form_field_id == $field->id || $val->field_name == $fieldName;
                });

                // Cek apakah ada input nilai baru
                $hasNewValue = array_key_exists($fieldId, $valuesInput) || 
                               array_key_exists($fieldName, $valuesInput) || 
                               $request->has("val_{$fieldId}") || 
                               $request->has("val_{$fieldName}");

                $rawValue = $valuesInput[$fieldId] ?? $valuesInput[$fieldName] ?? $request->input("val_{$fieldId}") ?? $request->input("val_{$fieldName}") ?? ($existingVal?->value_text);

                $valueText = $existingVal?->value_text;
                $valueNumber = $existingVal?->value_number;
                $valueJson = $existingVal?->value_json;
                $photoPath = $existingVal?->media_url;

                // Handle upload foto / media baru jika dikirimkan
                $savedPhotos = $this->saveUploadedPhotos($request, $submission->id, $fieldId, $fieldName);

                // Cek apakah ada existing photos yang dipertahankan dari request
                $existingPhotosInput = $request->input("existing_photos_{$fieldId}") ?? $request->input("existing_photos_{$fieldName}");
                $keptExistingPhotos = [];
                if ($existingPhotosInput) {
                    if (is_string($existingPhotosInput)) {
                        $rawArr = json_decode($existingPhotosInput, true) ?? [$existingPhotosInput];
                    } elseif (is_array($existingPhotosInput)) {
                        $rawArr = $existingPhotosInput;
                    } else {
                        $rawArr = [];
                    }
                    foreach ((array)$rawArr as $item) {
                        if (empty($item) || !is_string($item)) continue;
                        $cleaned = trim($item);
                        // Abaikan jika berupa path lokal perangkat android
                        if (str_starts_with($cleaned, '/data/user/') || str_starts_with($cleaned, 'data/user/') || str_contains($cleaned, 'cache/wm_')) {
                            continue;
                        }
                        if (str_contains($cleaned, '/storage/')) {
                            $parts = explode('/storage/', $cleaned);
                            $cleaned = ltrim(end($parts), '/');
                        } elseif (str_starts_with($cleaned, 'storage/')) {
                            $cleaned = ltrim(substr($cleaned, 8), '/');
                        }
                        $keptExistingPhotos[] = $cleaned;
                    }
                } elseif (empty($savedPhotos) && $existingVal) {
                    // Jika tidak ada upload baru dan tidak ada manipulasi existing photos, gunakan existing value
                    if (is_array($existingVal->value_json)) {
                        foreach ($existingVal->value_json as $item) {
                            if (empty($item) || !is_string($item)) continue;
                            $cleaned = trim($item);
                            if (str_starts_with($cleaned, '/data/user/') || str_starts_with($cleaned, 'data/user/') || str_contains($cleaned, 'cache/wm_')) {
                                continue;
                            }
                            if (str_contains($cleaned, '/storage/')) {
                                $parts = explode('/storage/', $cleaned);
                                $cleaned = ltrim(end($parts), '/');
                            } elseif (str_starts_with($cleaned, 'storage/')) {
                                $cleaned = ltrim(substr($cleaned, 8), '/');
                            }
                            $keptExistingPhotos[] = $cleaned;
                        }
                    } elseif ($existingVal->media_url) {
                        $cleaned = trim($existingVal->media_url);
                        if (!str_starts_with($cleaned, '/data/user/') && !str_starts_with($cleaned, 'data/user/') && !str_contains($cleaned, 'cache/wm_')) {
                            if (str_contains($cleaned, '/storage/')) {
                                $parts = explode('/storage/', $cleaned);
                                $cleaned = ltrim(end($parts), '/');
                            } elseif (str_starts_with($cleaned, 'storage/')) {
                                $cleaned = ltrim(substr($cleaned, 8), '/');
                            }
                            $keptExistingPhotos[] = $cleaned;
                        }
                    }
                }

                // Gabungkan foto yang dipertahankan + foto yang baru diupload
                $allPhotos = array_values(array_unique(array_filter(array_merge($keptExistingPhotos, $savedPhotos))));
                if (!empty($allPhotos)) {
                    $photoPath = $allPhotos[0];
                    $valueJson = $allPhotos;
                    $valueText = implode(', ', $allPhotos);
                }

                $isMediaField = in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature']);

                // Format data non-media
                if (!$isMediaField && $hasNewValue) {
                    if ($field->field_type === 'currency') {
                        if (is_numeric($rawValue)) {
                            $valueNumber = (float) $rawValue;
                        } elseif (is_string($rawValue)) {
                            // Bersihkan semua karakter non-digit untuk currency Rupiah ("Rp 195.000" -> 195000)
                            $cleanNum = preg_replace('/[^0-9]/', '', $rawValue);
                            $valueNumber = $cleanNum !== '' ? (float) $cleanNum : null;
                        }
                        $valueText = $rawValue !== null ? (string) $rawValue : null;
                    } elseif (in_array($field->field_type, ['number', 'integer', 'percentage', 'rating', 'rating_star', 'slider'])) {
                        if (is_numeric($rawValue)) {
                            $valueNumber = (float) $rawValue;
                        } elseif (is_string($rawValue)) {
                            // Normalisasi koma/titik desimal
                            $standardized = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $rawValue));
                            if (substr_count($standardized, '.') > 1) {
                                $standardized = str_replace('.', '', $standardized);
                            }
                            $valueNumber = is_numeric($standardized) ? (float) $standardized : null;
                        }
                        $valueText = $rawValue !== null ? (string) $rawValue : null;
                    } elseif (in_array($field->field_type, ['multi_select', 'checkbox_group', 'sku_list']) || is_array($rawValue)) {
                        $valueJson = is_array($rawValue) ? $rawValue : [$rawValue];
                        $valueText = is_array($rawValue) ? implode(', ', $rawValue) : (string) $rawValue;
                    } else {
                        $valueText = is_string($rawValue) ? $rawValue : ($rawValue !== null ? json_encode($rawValue) : null);
                    }
                }

                if ($photoPath && empty($valueText)) {
                    $valueText = $photoPath;
                }

                ReportSubmissionValue::updateOrCreate(
                    [
                        'report_submission_id' => $submission->id,
                        'report_form_field_id' => $field->id,
                    ],
                    [
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $valueText,
                        'value_number' => $valueNumber,
                        'value_json' => $valueJson,
                        'media_url' => $photoPath,
                    ]
                );
            }

            // Update/simpan data_kompetitor_list jika dikirimkan dari formulir dinamis CBP
            if (isset($valuesInput['data_kompetitor_list'])) {
                $compListRaw = $valuesInput['data_kompetitor_list'];
                if (is_string($compListRaw)) {
                    $compListRaw = json_decode($compListRaw, true) ?? [];
                }
                if (is_array($compListRaw) && !empty($compListRaw)) {
                    ReportSubmissionValue::updateOrCreate(
                        [
                            'report_submission_id' => $submission->id,
                            'field_name' => 'data_kompetitor_list',
                        ],
                        [
                            'report_form_field_id' => null,
                            'field_type' => 'json',
                            'value_text' => json_encode($compListRaw),
                            'value_number' => count($compListRaw),
                            'value_json' => $compListRaw,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil diperbarui.',
                'data' => [
                    'id' => $submission->id,
                    'submission_code' => $submission->submission_code,
                    'status' => $submission->status,
                    'updated_at' => $submission->updated_at->toDateTimeString(),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to collect and save uploaded files for a field (single or multi-photo).
     * @return array array of saved relative file paths
     */
    private function saveUploadedPhotos(Request $request, int $submissionId, string $fieldId, string $fieldName): array
    {
        $savedPaths = [];
        $uploadedFiles = [];

        // 1. Cek jika dikirim sebagai array photo_{fieldId}[] atau photos_{fieldId}[]
        if ($request->hasFile("photo_{$fieldId}")) {
            $f = $request->file("photo_{$fieldId}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }
        if ($request->hasFile("photos_{$fieldId}")) {
            $f = $request->file("photos_{$fieldId}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }
        if ($request->hasFile("photo_{$fieldName}")) {
            $f = $request->file("photo_{$fieldName}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }

        // 2. Cek jika dikirim dengan suffix index (misal: photo_{fieldId}_0, photo_{fieldId}_1, dst)
        foreach ($request->allFiles() as $key => $file) {
            if (str_starts_with($key, "photo_{$fieldId}_") || str_starts_with($key, "photos_{$fieldId}_") || str_starts_with($key, "photo_{$fieldName}_")) {
                if (is_array($file)) {
                    $uploadedFiles = array_merge($uploadedFiles, $file);
                } else {
                    $uploadedFiles[] = $file;
                }
            }
        }

        $seenHashes = [];
        foreach ($uploadedFiles as $idx => $file) {
            if ($file && $file->isValid()) {
                $hash = @md5_file($file->getRealPath());
                if ($hash && isset($seenHashes[$hash])) {
                    continue;
                }
                if ($hash) {
                    $seenHashes[$hash] = true;
                }
                $filename = "report_{$submissionId}_{$fieldId}_{$idx}_" . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("reports/" . now()->format('Y-m'), $filename, 'public');
                if ($path) {
                    $savedPaths[] = $path;
                }
            }
        }

        return $savedPaths;
    }

    /**
     * Helper to format media full URLs cleanly and prevent duplicate/broken paths.
     */
    private function formatMediaFullUrls($v): array
    {
        $rawPaths = [];
        if (is_array($v->value_json) && !empty($v->value_json)) {
            $rawPaths = $v->value_json;
        } elseif (!empty($v->media_url)) {
            $rawPaths = [$v->media_url];
        } elseif (!empty($v->file_path)) {
            $rawPaths = [$v->file_path];
        } elseif (!empty($v->value_text) && (str_contains($v->value_text, 'reports/') || str_contains($v->value_text, 'storage/'))) {
            $rawPaths = array_map('trim', explode(',', $v->value_text));
        }

        $urls = [];
        foreach ($rawPaths as $p) {
            if (empty($p) || !is_string($p)) continue;
            $clean = trim($p);
            // Abaikan jika berupa path lokal perangkat android
            if (str_starts_with($clean, '/data/user/') || str_starts_with($clean, 'data/user/') || str_contains($clean, 'cache/wm_')) {
                continue;
            }
            if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
                $urls[] = str_replace('/storage/storage/', '/storage/', $clean);
            } else {
                if (str_starts_with($clean, 'storage/')) {
                    $clean = substr($clean, 8);
                } elseif (str_starts_with($clean, '/storage/')) {
                    $clean = substr($clean, 9);
                }
                $urls[] = asset('storage/' . ltrim($clean, '/'));
            }
        }

        // Fallback: Jika urls kosong padahal ini media field, cari file di disk yang sesuai
        if (empty($urls) && in_array($v->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
            $subId = $v->report_submission_id;
            $fieldId = $v->report_form_field_id;
            $pattern = "reports/*/report_{$subId}_{$fieldId}_*.jpg";
            $matches = glob(storage_path("app/public/{$pattern}"));
            if (empty($matches)) {
                $pattern2 = "reports/*/report_{$subId}_*.jpg";
                $matches = glob(storage_path("app/public/{$pattern2}"));
            }
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $rel = str_replace(storage_path('app/public/'), '', $match);
                    $rel = str_replace('\\', '/', $rel);
                    $urls[] = asset('storage/' . ltrim($rel, '/'));
                }
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * Delete an existing report submission.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $submission = ReportSubmission::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$submission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Laporan tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        $submission->values()->delete();
        $submission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan berhasil dihapus.',
        ]);
    }

    /**
     * Clear report submissions for today (for fresh testing / reset).
     */
    public function clearToday(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $date = $request->input('date', Carbon::today('Asia/Jakarta')->toDateString());

        $query = ReportSubmission::query();
        if (!$request->boolean('all_users')) {
            $query->where('employee_id', $employee->id);
        }

        $submissions = $query->where(function ($q) use ($date) {
            $q->whereDate('submitted_at', $date)
              ->orWhereDate('created_at', $date);
        })->get();

        $count = $submissions->count();
        foreach ($submissions as $sub) {
            $sub->values()->delete();
            $sub->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil menghapus {$count} laporan untuk tanggal {$date}.",
            'deleted_count' => $count,
        ]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta    = deg2rad($lat2 - $lat1);
        $lonDelta    = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check reporting compliance for Checkout or Visit-out.
     */
    public function checkCompliance(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $type = $request->query('type', 'checkout');
        $locationId = $request->query('work_location_id') ?: $request->query('store_id');

        $pending = self::checkPendingReportsStatic($employee, $type, $locationId ? (int)$locationId : null);

        return response()->json([
            'status' => 'success',
            'can_proceed' => empty($pending),
            'type' => $type,
            'pending_reports' => $pending,
            'pending_count' => count($pending),
            'message' => empty($pending) 
                ? 'Semua laporan wajib telah diselesaikan.' 
                : 'Terdapat laporan wajib yang belum diselesaikan.',
        ]);
    }

    /**
     * Helper statis untuk mengecek laporan wajib yang belum selesai hari ini.
     */
    public static function checkPendingReportsStatic(\App\Models\Employee $employee, string $type = 'checkout', ?int $workLocationId = null): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $todayStr = $now->toDateString();

        // 1. Dapatkan principal IDs yang relevan
        $scopedPrincipalIds = [];
        if ($employee->principal_id) {
            $scopedPrincipalIds[] = $employee->principal_id;
        }
        if ($employee->principal) {
            $scopedPrincipalIds[] = $employee->principal->id;
            $principalNameUpper = strtoupper($employee->principal->name);
            if (Str::contains($principalNameUpper, 'DULUX') || Str::contains($principalNameUpper, 'AKZONOBEL') || Str::contains($principalNameUpper, 'ICI')) {
                $duluxIds = Principal::where('name', 'LIKE', '%DULUX%')
                    ->orWhere('name', 'LIKE', '%AKZONOBEL%')
                    ->orWhere('name', 'LIKE', '%ICI%')
                    ->orWhere('subdomain', 'dulux')
                    ->pluck('id')->toArray();
                $scopedPrincipalIds = array_merge($scopedPrincipalIds, $duluxIds);
            }
        }
        $scopedPrincipalIds = array_values(array_unique(array_filter($scopedPrincipalIds)));

        // 2. Query template aktif
        $query = ReportTemplate::where('is_active', true)
            ->with(['principals', 'positions', 'employees', 'products', 'fields']);

        if (!empty($scopedPrincipalIds)) {
            $query->where(function ($q) use ($scopedPrincipalIds) {
                $q->whereIn('principal_id', $scopedPrincipalIds)
                  ->orWhereHas('principals', function ($pq) use ($scopedPrincipalIds) {
                      $pq->whereIn('principals.id', $scopedPrincipalIds);
                  });
            });
        }

        $templates = $query->get()->filter(function ($t) use ($employee) {
            $hasAssignedEmployees = $t->employees->isNotEmpty();
            $hasAssignedPositions = $t->positions->isNotEmpty();
            if ($hasAssignedEmployees && !$t->employees->contains('id', $employee->id)) {
                return false;
            }
            if ($hasAssignedPositions && (!$employee->position_id || !$t->positions->contains('id', $employee->position_id))) {
                return false;
            }
            return true;
        })->values();

        $duluxOrder = [
            'RPT-DULUX-DAILY-MAINTENANCE' => 1,
            'RPT-DULUX-OFFTAKE-01' => 2,
            'RPT-DULUX-OOS-SSO' => 3,
            'RPT-DULUX-DATABASE-PELANGGAN' => 4,
            'RPT-DULUX-STOCK-END' => 5,
            'RPT-DULUX-CBP-PRICING' => 6,
        ];

        $templates = $templates->sortBy(function ($t) use ($duluxOrder) {
            return $duluxOrder[$t->code] ?? 999;
        })->values();

        // 3. Ambil submissions hari ini
        $todaySubsQuery = ReportSubmission::where('employee_id', $employee->id)
            ->whereDate('submitted_at', $todayStr)
            ->with('values');

        $todaySubmissions = $todaySubsQuery->get();

        if (!$workLocationId) {
            $todayVisitIn = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('created_at', $todayStr)
                ->where('log_type', 'visit_in')
                ->latest()
                ->first();
            if ($todayVisitIn && !empty($todayVisitIn->metadata['visit_location_id'])) {
                $workLocationId = (int)$todayVisitIn->metadata['visit_location_id'];
            }
            if (!$workLocationId) {
                $todayAtt = \App\Models\Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $todayStr)
                    ->first();
                if ($todayAtt) {
                    $workLocationId = $todayAtt->work_location_id 
                        ?? $todayAtt->checkinLog?->metadata['visit_location_id'] 
                        ?? $todayAtt->schedule?->work_location_id 
                        ?? $employee->work_location_id;
                } else {
                    $workLocationId = $employee->work_location_id;
                }
            }
        }
        $targetLocation = $workLocationId ? \App\Models\WorkLocation::find($workLocationId) : null;

        $pending = [];

        foreach ($templates as $t) {
            $scheduleType = strtolower($t->schedule_type ?? 'daily');
            
            // Cek apakah template dijadwalkan hari ini
            $isDueToday = false;
            if ($scheduleType === 'daily') {
                $isDueToday = $t->isScheduledForDate($now);
            } elseif ($scheduleType === 'weekly') {
                $isDueToday = $t->isScheduledForDate($now);
            } elseif ($scheduleType === 'monthly') {
                // Untuk Laporan Monthly:
                // Cek settingan Rentang Tanggal Harus Lapor (monthly_start_day s/d monthly_end_day)
                // Jika hari ini berada di luar rentang tanggal, laporan dilewati (tidak wajib lapor) dan TIDAK memblokir check-out!
                $start = $t->monthly_start_day ? (int)$t->monthly_start_day : null;
                $end = $t->monthly_end_day ? (int)$t->monthly_end_day : ($t->monthly_due_day ? (int)$t->monthly_due_day : null);
                if ($start !== null || $end !== null) {
                    $startVal = $start ?? 1;
                    $endVal = $end ?? 31;
                    if ($now->day < $startVal || $now->day > $endVal) {
                        $isDueToday = false;
                    } else {
                        $isDueToday = true;
                    }
                } else {
                    $isDueToday = true;
                }
            } else {
                $isDueToday = true;
            }

            if (!$isDueToday) {
                continue;
            }

            // Filter submissions untuk template ini
            $tSubs = $todaySubmissions->where('report_template_id', $t->id);
            if ($workLocationId) {
                $tSubs = $tSubs->filter(function($s) use ($workLocationId) {
                    return empty($s->work_location_id) || $s->work_location_id == $workLocationId;
                });
            }

            // Khusus Laporan Monthly yang sudah jatuh tempo:
            // Cek apakah sudah disubmit di bulan berjalan (cut-off)
            if ($scheduleType === 'monthly') {
                $monthStart = $now->copy()->startOfMonth()->toDateString();
                $monthEnd = $now->copy()->endOfMonth()->toDateString();
                $monthlySubCount = ReportSubmission::where('report_template_id', $t->id)
                    ->where('employee_id', $employee->id)
                    ->whereBetween(DB::raw('DATE(submitted_at)'), [$monthStart, $monthEnd])
                    ->count();

                $targetMonthly = max(1, (int)($t->target_count ?? 1));
                if ($monthlySubCount >= $targetMonthly) {
                    // Sudah disubmit untuk bulan ini, tidak pending!
                    continue;
                } else {
                    // Belum disubmit pada bulan ini padahal sudah tanggal jatuh tempo -> WAJIB lapor, blokir check-out!
                    $pending[] = $t->title;
                    continue;
                }
            }

            // Khusus Laporan Daily Maintenance Dulux: Validasi seluruh mesin terdaftar telah dilaporkan
            if ($t->code === 'RPT-DULUX-DAILY-MAINTENANCE' && $targetLocation) {
                $storeMachines = $targetLocation->normalized_machines;
                if (!empty($storeMachines)) {
                    $submittedMachineNames = [];
                    foreach ($tSubs as $sub) {
                        foreach ($sub->values as $val) {
                            $fName = strtolower($val->field_name ?? '');
                            if ($fName === 'tipe_mesin_post' || $fName === 'tipe_mesin') {
                                $mVal = trim((string)($val->value_text ?? ''));
                                if ($mVal !== '') {
                                    $submittedMachineNames[] = strtolower($mVal);
                                }
                            }
                        }
                    }
                    $submittedMachineNames = array_unique($submittedMachineNames);

                    $unreportedMachines = [];
                    foreach ($storeMachines as $sm) {
                        $mType = trim($sm['machine_type']);
                        if (!in_array(strtolower($mType), $submittedMachineNames)) {
                            $unreportedMachines[] = $mType;
                        }
                    }

                    if (!empty($unreportedMachines)) {
                        $pending[] = "{$t->title} (Sisa " . count($unreportedMachines) . " mesin: " . implode(', ', $unreportedMachines) . ")";
                    }
                    continue;
                }
            }

            // Khusus template alur pelaporan berurutan Dulux lainnya (Offtake, OOS, Data Pelanggan, Stock End, CBP Pricing)
            if (isset($duluxOrder[$t->code])) {
                if ($tSubs->isEmpty()) {
                    $pending[] = $t->title;
                }
                continue;
            }

            // Cek produk jika template mengikat produk
            $tProducts = $t->products;
            if ($tProducts->isEmpty()) {
                if (Str::startsWith($t->code, 'RPT-DULUX-') || Str::contains(strtoupper($t->title), 'DULUX')) {
                    $cleanIds = Principal::where('name', 'LIKE', '%DULUX%')
                        ->orWhere('name', 'LIKE', '%AKZONOBEL%')
                        ->orWhere('name', 'LIKE', '%ICI%')
                        ->orWhere('subdomain', 'dulux')
                        ->pluck('id')->toArray();
                    if (!empty($cleanIds)) {
                        $tProducts = \App\Models\Product::whereIn('principal_id', $cleanIds)
                            ->where(function ($q) {
                                $q->where('is_active', true)->orWhere('is_active', 1)->orWhereNull('is_active');
                            })->get();
                    }
                }
            }

            $prodFieldNames = $t->fields->filter(function($f) {
                $name = strtolower($f->field_name);
                $type = strtolower($f->field_type);
                return $type === 'product_select' || $type === 'product' || in_array($name, ['produk_stock_end', 'produk_oos', 'produk', 'product', 'sub_brand', 'subbrand_produk']);
            })->pluck('field_name')->toArray();

            $hasProductBinding = !empty($prodFieldNames) && $tProducts->isNotEmpty();

            if ($hasProductBinding) {
                $submittedProdNames = [];
                foreach ($tSubs as $sub) {
                    foreach ($sub->values as $val) {
                        if (in_array($val->field_name, $prodFieldNames) || in_array($val->field_type, ['product_select', 'product'])) {
                            $pName = trim((string)($val->value_text ?? ''));
                            if ($pName !== '') {
                                $submittedProdNames[] = strtolower($pName);
                            }
                        }
                    }
                }
                $submittedProdNames = array_unique($submittedProdNames);

                if (count($submittedProdNames) < $tProducts->count()) {
                    $sisa = $tProducts->count() - count($submittedProdNames);
                    $pending[] = "{$t->title} (Sisa {$sisa} produk)";
                }
            } else {
                if ($tSubs->isEmpty()) {
                    $pending[] = $t->title;
                }
            }
        }

        return $pending;
    }

    /**
     * API endpoint untuk mengambil riwayat OOS toko sebelumnya beserta kalkulasi lama hari.
     */
    public function oosHistory(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $storeId = $request->query('store_id') ?? $request->query('work_location_id');
        $templateId = $request->query('template_id');

        $ref = $this->getStoreOosReference($storeId ? (int)$storeId : null, $templateId ? (int)$templateId : null);

        return response()->json([
            'status' => 'success',
            'data' => $ref,
        ]);
    }

    /**
     * Dapatkan riwayat OOS sebelumnya untuk toko tertentu dan hitung pertambahan hari otomatis.
     */
    protected function getStoreOosReference(?int $storeId, ?int $templateId = null): array
    {
        if (!$storeId) {
            return [
                'has_previous' => false,
                'last_submission_date' => null,
                'diff_days' => 1,
                'previous_items' => [],
            ];
        }

        $today = now()->toDateString();

        // Cari submission OOS terakhir di toko ini SEBELUM hari ini
        $lastSub = ReportSubmission::where('work_location_id', $storeId)
            ->whereHas('template', function ($q) use ($templateId) {
                if ($templateId) {
                    $q->where('id', $templateId);
                } else {
                    $q->where('code', 'RPT-DULUX-OOS-SSO')->orWhere('code', 'LIKE', '%OOS%');
                }
            })
            ->whereDate('submitted_at', '<', $today)
            ->with(['values.formField', 'template'])
            ->orderBy('submitted_at', 'desc')
            ->first();

        if (!$lastSub) {
            return [
                'has_previous' => false,
                'last_submission_date' => null,
                'diff_days' => 1,
                'previous_items' => [],
            ];
        }

        $subDate = \Illuminate\Support\Carbon::parse($lastSub->submitted_at)->startOfDay();
        $todayDate = now()->startOfDay();
        $diffDays = max(1, (int)$subDate->diffInDays($todayDate));

        // Kumpulkan values
        $valMap = [];
        foreach ($lastSub->values as $v) {
            $fName = strtolower(trim($v->field_name ?? ($v->formField?->field_name ?? '')));
            if ($fName) {
                $valMap[$fName] = $v->value_json ?? ($v->value_text ?? $v->value_number);
            }
        }

        $tipeOos = strtolower(trim((string)($valMap['tipe_laporan_oos'] ?? '')));
        $statusOos = strtolower(trim((string)($valMap['status_ketersediaan'] ?? ($valMap['alasan_oos'] ?? ''))));

        // Jika laporan terakhir adalah No OOS / Stok Lengkap, maka stok toko sudah pulih!
        // Riwayat OOS di-reset dari awal (kosong).
        if ($tipeOos === 'no_oos' || str_contains($statusOos, 'no oos') || str_contains($statusOos, 'stok lengkap')) {
            return [
                'has_previous' => true,
                'is_last_no_oos' => true,
                'last_submission_date' => $lastSub->submitted_at->toDateString(),
                'diff_days' => $diffDays,
                'previous_items' => [],
            ];
        }

        $items = [];

        // 1. Cek dari oos_items_json jika ada
        $rawOosVal = $valMap['oos_items_json'] ?? null;
        if (empty($rawOosVal) || is_numeric($rawOosVal)) {
            $vObj = $lastSub->values->firstWhere('field_name', 'oos_items_json');
            $rawOosVal = $vObj?->value_json ?? $vObj?->value_text;
        }
        if (!empty($rawOosVal)) {
            $rawItems = is_array($rawOosVal) ? $rawOosVal : json_decode((string)$rawOosVal, true);
            if (is_array($rawItems)) {
                foreach ($rawItems as $itm) {
                    $prevLama = max(1, (int)($itm['lama_oos_hari'] ?? 1));
                    $calcLama = $prevLama + $diffDays;
                    $items[] = [
                        'product_id' => $itm['product_id'] ?? null,
                        'product_name' => $itm['product_name'] ?? ($itm['produk_oos'] ?? ''),
                        'kemasan_size' => $itm['kemasan_size'] ?? ($itm['kemasan_size_oos'] ?? ''),
                        'kemasan_size_oos' => $itm['kemasan_size_oos'] ?? ($itm['kemasan_size'] ?? ''),
                        'base_color' => $itm['base_color'] ?? ($itm['base_warna_oos'] ?? ''),
                        'base_warna_oos' => $itm['base_warna_oos'] ?? ($itm['base_color'] ?? ''),
                        'warna_ready_mix_oos' => $itm['warna_ready_mix_oos'] ?? '',
                        'lama_oos_hari' => $prevLama,
                        'previous_lama_oos' => $prevLama,
                        'calculated_lama_oos' => $calcLama,
                        'alasan_oos' => $itm['alasan_oos'] ?? '',
                        'saran_qty_order' => (int)($itm['saran_qty_order'] ?? 0),
                    ];
                }
            }
        }

        // 2. Jika tidak ada oos_items_json (format single submission lama):
        if (empty($items)) {
            // Ambil semua submission OOS pada tanggal laporan terakhir tersebut
            $sameDaySubs = ReportSubmission::where('work_location_id', $storeId)
                ->whereHas('template', function ($q) use ($templateId) {
                    if ($templateId) {
                        $q->where('id', $templateId);
                    } else {
                        $q->where('code', 'RPT-DULUX-OOS-SSO')->orWhere('code', 'LIKE', '%OOS%');
                    }
                })
                ->whereDate('submitted_at', $lastSub->submitted_at->toDateString())
                ->with(['values.formField'])
                ->get();

            foreach ($sameDaySubs as $sSub) {
                $sValMap = [];
                foreach ($sSub->values as $v) {
                    $fn = strtolower(trim($v->field_name ?? ($v->formField?->field_name ?? '')));
                    if ($fn) {
                        $sValMap[$fn] = $v->value_json ?? ($v->value_number ?? $v->value_text);
                    }
                }
                $pName = trim((string)($sValMap['produk_oos'] ?? ($sValMap['nama_produk_yang_kosong_oos'] ?? '')));
                if (!empty($pName) && !str_contains(strtolower($pName), 'no oos')) {
                    $prevLama = max(1, (int)($sValMap['lama_oos_hari'] ?? ($sValMap['lama_kondisi_barang_kosong_jumlah_hari'] ?? 1)));
                    $calcLama = $prevLama + $diffDays;
                    $items[] = [
                        'product_id' => null,
                        'product_name' => $pName,
                        'kemasan_size' => $sValMap['kemasan_size_oos'] ?? '',
                        'kemasan_size_oos' => $sValMap['kemasan_size_oos'] ?? '',
                        'base_color' => $sValMap['base_warna_oos'] ?? '',
                        'base_warna_oos' => $sValMap['base_warna_oos'] ?? '',
                        'warna_ready_mix_oos' => $sValMap['warna_ready_mix_oos'] ?? '',
                        'lama_oos_hari' => $prevLama,
                        'previous_lama_oos' => $prevLama,
                        'calculated_lama_oos' => $calcLama,
                        'alasan_oos' => $sValMap['alasan_oos'] ?? '',
                        'saran_qty_order' => (int)($sValMap['saran_qty_order'] ?? 0),
                    ];
                }
            }
        }

        return [
            'has_previous' => count($items) > 0,
            'is_last_no_oos' => false,
            'last_submission_date' => $lastSub->submitted_at->toDateString(),
            'diff_days' => $diffDays,
            'previous_items' => $items,
        ];
    }

    /**
     * Pencarian profil pelanggan berdasarkan No. HP / WhatsApp
     * Mencakup data PostgreSQL live submissions dan arsip SQLite customer_db.
     */
    public function customerLookup(Request $request)
    {
        $rawPhone = trim((string)($request->query('phone') ?? $request->query('no_hp') ?? ''));
        if (empty($rawPhone)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter phone / no_hp wajib diisi.',
                'found' => false,
                'customer' => null,
                'data' => null,
            ], 422);
        }

        // Normalisasi nomor telepon: buang karakter non-digit
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($cleanPhone, '62')) {
            $normalizedPhone = '0' . substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $normalizedPhone = '0' . $cleanPhone;
        } else {
            $normalizedPhone = $cleanPhone;
        }
        $digitsWithoutZero = ltrim($normalizedPhone, '0');

        // Varian pencarian nomor telepon
        $searchVariants = array_filter(array_unique([
            $rawPhone,
            $cleanPhone,
            $normalizedPhone,
            $digitsWithoutZero,
            strlen($cleanPhone) >= 8 ? substr($cleanPhone, -8) : null,
            strlen($cleanPhone) >= 9 ? substr($cleanPhone, -9) : null,
            strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : null,
        ]));

        // Target field name untuk nomor HP konsumen
        $targetFieldNames = [
            'no_hp_pelanggan',
            'nomor_hp_whatsapp_pelanggan',
            'nomor_hp_pelanggan',
            'no_hp',
            'nomor_hp',
            'no_telepon',
            'telepon_pelanggan',
            'whatsapp',
        ];

        // 1. Cari di PostgreSQL report_submissions (template yang berkaitan dengan database pelanggan)
        $templateIds = ReportTemplate::where(function ($q) {
            $q->where('code', 'LIKE', '%DATABASE-PELANGGAN%')
              ->orWhere('code', 'LIKE', '%DATA-PELANGGAN%')
              ->orWhere('code', 'LIKE', '%PELANGGAN%')
              ->orWhere('title', 'LIKE', '%Pelanggan%');
        })->pluck('id')->toArray();

        $subValQuery = ReportSubmissionValue::where(function ($q) use ($targetFieldNames) {
            $q->whereIn('field_name', $targetFieldNames)
              ->orWhere('field_name', 'LIKE', '%no_hp%')
              ->orWhere('field_name', 'LIKE', '%nomor_hp%');
        });

        if (!empty($templateIds)) {
            $subValQuery->whereHas('submission', function ($q) use ($templateIds) {
                $q->whereIn('report_template_id', $templateIds);
            });
        }

        $subValQuery->where(function ($q) use ($searchVariants) {
            foreach ($searchVariants as $variant) {
                $q->orWhere('value_text', 'LIKE', "%{$variant}%");
            }
        });

        $candidates = $subValQuery->latest('id')->take(10)->get();

        foreach ($candidates as $cand) {
            $valClean = preg_replace('/[^0-9]/', '', (string)$cand->value_text);
            $isMatch = false;
            if ($valClean === $cleanPhone || $valClean === $normalizedPhone || $valClean === $digitsWithoutZero) {
                $isMatch = true;
            } elseif (strlen($valClean) >= 8 && strlen($cleanPhone) >= 8 && substr($valClean, -8) === substr($cleanPhone, -8)) {
                $isMatch = true;
            } elseif (stripos((string)$cand->value_text, $cleanPhone) !== false || stripos((string)$cand->value_text, $normalizedPhone) !== false) {
                $isMatch = true;
            }

            if ($isMatch && $cand->submission) {
                $sub = $cand->submission;
                $valMap = [];
                foreach ($sub->values as $v) {
                    $fn = strtolower(trim((string)$v->field_name));
                    $valMap[$fn] = $v->value_text;
                    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$v->field_name), '_'));
                    $valMap[$slug] = $v->value_text;
                    if ($v->formField) {
                        $fNameSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$v->formField->field_name), '_'));
                        $valMap[$fNameSlug] = $v->value_text;
                        $labelSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$v->formField->field_label), '_'));
                        $valMap[$labelSlug] = $v->value_text;
                    }
                }

                $nama = trim((string)($valMap['nama_lengkap_pelanggan'] ?? ($valMap['nama_pelanggan'] ?? ($valMap['nama_konsumen'] ?? ($valMap['nama'] ?? '')))));
                if (!empty($nama)) {
                    $alamat = trim((string)($valMap['alamat_domisili_pelanggan'] ?? ($valMap['alamat_pelanggan'] ?? ($valMap['alamat_konsumen'] ?? ($valMap['alamat'] ?? '')))));
                    $tipe = trim((string)($valMap['tipe_kategori_pelanggan'] ?? ($valMap['tipe_pelanggan'] ?? ($valMap['tipe_konsumen'] ?? 'Pemilik Rumah'))));
                    $loyalty = trim((string)($valMap['painter_loyalty'] ?? ($valMap['program_mitra_dulux_painter_loyalty'] ?? ($valMap['program_mitra_dulux'] ?? 'Tidak Bersedia'))));

                    $customerPayload = [
                        'no_hp_pelanggan' => $normalizedPhone,
                        'nama_pelanggan' => $nama,
                        'alamat_pelanggan' => $alamat,
                        'tipe_pelanggan' => $tipe,
                        'painter_loyalty' => $loyalty,
                        // Aliases for comprehensive compatibility
                        'nama_lengkap_pelanggan' => $nama,
                        'alamat_domisili_pelanggan' => $alamat,
                        'tipe_kategori_pelanggan' => $tipe,
                        'nomor_hp_whatsapp_pelanggan' => $normalizedPhone,
                    ];

                    return response()->json([
                        'status' => 'success',
                        'found' => true,
                        'source' => 'live_database',
                        'customer' => $customerPayload,
                        'data' => $customerPayload,
                    ]);
                }
            }
        }

        // 2. Cari di SQLite customer_db.sqlite
        try {
            $sqlitePath = storage_path('app/dulux_data/customer_db.sqlite');
            $gzPath     = storage_path('app/dulux_data/customer_db.sqlite.gz');

            if (!file_exists($sqlitePath) && file_exists($gzPath)) {
                $zp = gzopen($gzPath, 'rb');
                $fp = fopen($sqlitePath, 'wb');
                if ($zp && $fp) {
                    while (!gzeof($zp)) {
                        fwrite($fp, gzread($zp, 524288));
                    }
                    gzclose($zp);
                    fclose($fp);
                    @chmod($sqlitePath, 0666);
                }
            }

            if (file_exists($sqlitePath)) {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $searchPattern = "%" . (strlen($normalizedPhone) >= 8 ? substr($normalizedPhone, -8) : $normalizedPhone) . "%";
                $stmt = $pdo->prepare("
                    SELECT nama_pelanggan, alamat, no_hp, tipe_pelanggan, painter_info
                    FROM cust_raw
                    WHERE no_hp LIKE ? OR no_hp LIKE ? OR no_hp LIKE ?
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $stmt->execute([$searchPattern, "%{$normalizedPhone}%", "%{$digitsWithoutZero}%"]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($row && !empty(trim((string)($row['nama_pelanggan'] ?? '')))) {
                    $painterInfo = (string)($row['painter_info'] ?? '');
                    $loyalty = (stripos($painterInfo, 'CHECKED') !== false || stripos($painterInfo, 'bersedia') !== false)
                        ? 'Saya bersedia menerima informasi mengenai program Mitra Dulux'
                        : 'Tidak Bersedia';

                    $customerPayload = [
                        'no_hp_pelanggan' => $normalizedPhone,
                        'nama_pelanggan' => trim((string)$row['nama_pelanggan']),
                        'alamat_pelanggan' => trim((string)($row['alamat'] ?? '')),
                        'tipe_pelanggan' => trim((string)($row['tipe_pelanggan'] ?? 'Pemilik Rumah')),
                        'painter_loyalty' => $loyalty,
                        'nama_lengkap_pelanggan' => trim((string)$row['nama_pelanggan']),
                        'alamat_domisili_pelanggan' => trim((string)($row['alamat'] ?? '')),
                        'tipe_kategori_pelanggan' => trim((string)($row['tipe_pelanggan'] ?? 'Pemilik Rumah')),
                        'nomor_hp_whatsapp_pelanggan' => $normalizedPhone,
                    ];

                    return response()->json([
                        'status' => 'success',
                        'found' => true,
                        'source' => 'archive_database',
                        'customer' => $customerPayload,
                        'data' => $customerPayload,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Customer lookup SQLite check error: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'found' => false,
            'message' => 'Data pelanggan belum pernah terdaftar sebelumnya.',
            'customer' => null,
            'data' => null,
        ]);
    }

    /**
     * Get Master Data Competitor Products for CBP Reporting
     */
    public function competitorProducts(Request $request): JsonResponse
    {
        $principalId = $request->query('principal_id');
        $user = $request->user();
        if (!$principalId && $user) {
            $principalId = $user->principal_id ?? ($user->employee?->principal_id ?? null);
        }

        $isDulux = true;
        if ($principalId) {
            $p = \App\Models\Principal::find($principalId);
            if ($p && !(str_contains(strtoupper($p->name), 'DULUX') || str_contains(strtoupper($p->name), 'ICI') || str_contains(strtoupper($p->code ?? ''), 'DULUX') || str_contains(strtoupper($p->subdomain ?? ''), 'dulux'))) {
                $isDulux = false;
            }
        }

        $standardBrands = $isDulux ? [
            'JOTUN',
            'NIPPON PAINT',
            'AVIAN / NO DROP / LENKOTE',
            'MOWILEX',
            'PROPAN',
            'KANSAI / DANAPAINT',
            'PACIFIC PAINT',
            'MERK LAINNYA',
        ] : ['MERK LAINNYA'];

        $subbrandsByBrand = [];
        $allProducts = [];
        $finalBrands = $standardBrands;

        try {
            if (class_exists(\App\Models\CompetitorProduct::class) && \Illuminate\Support\Facades\Schema::hasTable('competitor_products')) {
                $query = \App\Models\CompetitorProduct::where('is_active', true)
                    ->orderBy('order_index')
                    ->orderBy('brand')
                    ->orderBy('subbrand');

                if ($principalId) {
                    $query->where('principal_id', $principalId);
                }

                if ($request->has('brand') && !empty($request->brand)) {
                    $query->where('brand', $request->brand);
                }

                $records = $query->get();

                if ($records->isNotEmpty()) {
                    $dbBrands = $records->pluck('brand')->unique()->values()->toArray();
                    foreach ($standardBrands as $db) {
                        if (!in_array($db, $dbBrands)) {
                            $dbBrands[] = $db;
                        }
                    }
                    $finalBrands = $dbBrands;

                    foreach ($records as $item) {
                        $b = $item->brand;
                        if (!isset($subbrandsByBrand[$b])) {
                            $subbrandsByBrand[$b] = [];
                        }
                        $payload = [
                            'id' => $item->id,
                            'brand' => $item->brand,
                            'subbrand' => $item->subbrand,
                            'name' => $item->subbrand,
                            'category' => $item->category,
                            'packaging_sizes' => $item->packaging_sizes ?? ['Tin', 'Galon', 'Pail'],
                            'benchmark_price_tin' => (float)($item->benchmark_price_tin ?? 0),
                            'benchmark_price_galon' => (float)($item->benchmark_price_galon ?? 0),
                            'benchmark_price_pail' => (float)($item->benchmark_price_pail ?? 0),
                        ];
                        $subbrandsByBrand[$b][] = $payload;
                        $allProducts[] = $payload;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Error fetching competitor products from DB: " . $e->getMessage());
        }

        // Pastikan setiap brand standar memiliki minimal opsi 'LAINNYA / INPUT MANUAL'
        foreach ($finalBrands as $b) {
            if (!isset($subbrandsByBrand[$b]) || empty($subbrandsByBrand[$b])) {
                $subbrandsByBrand[$b] = [
                    [
                        'id' => 0,
                        'brand' => $b,
                        'subbrand' => 'LAINNYA / INPUT MANUAL',
                        'name' => 'LAINNYA / INPUT MANUAL',
                        'category' => 'Umum',
                        'packaging_sizes' => ['Tin', 'Galon', 'Pail'],
                        'benchmark_price_tin' => 0,
                        'benchmark_price_galon' => 0,
                        'benchmark_price_pail' => 0,
                    ]
                ];
            } else {
                // Tambahkan opsi lainnya di akhir jika belum ada
                $hasManual = collect($subbrandsByBrand[$b])->contains(function ($item) {
                    return str_contains(strtoupper($item['subbrand']), 'LAINNYA');
                });
                if (!$hasManual) {
                    $subbrandsByBrand[$b][] = [
                        'id' => 0,
                        'brand' => $b,
                        'subbrand' => 'LAINNYA / INPUT MANUAL',
                        'name' => 'LAINNYA / INPUT MANUAL',
                        'category' => 'Umum',
                        'packaging_sizes' => ['Tin', 'Galon', 'Pail'],
                        'benchmark_price_tin' => 0,
                        'benchmark_price_galon' => 0,
                        'benchmark_price_pail' => 0,
                    ];
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'brands' => $finalBrands,
            'subbrands_by_brand' => $subbrandsByBrand,
            'products' => $allProducts,
            'total' => count($allProducts),
        ]);
    }
}


