<?php

namespace App\Filament\Pages;

use App\Exports\TeamUncheckedExport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TeamUncheckedMonitoring extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance & Time Management';
    protected static ?string $navigationLabel = 'Monitoring Belum Check-in';
    protected static ?string $title = 'Monitoring Tim Belum Check-in';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.team-unchecked-monitoring';

    // Filters
    public ?string $selectedPrincipalId = null;
    public ?string $selectedBranchId = null;
    public ?string $searchQuery = '';
    public string $quickFilter = 'all'; // 'all', 'today', 'ge3', 'never'

    // Interactive Cell Filter from Matrix (Gambar 1)
    public ?string $selectedCellPrincipalId = null;
    public ?string $selectedCellBranchId = null;
    public ?string $selectedCellPrincipalName = null;
    public ?string $selectedCellBranchName = null;

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->selectedPrincipalId = null;
        $this->selectedBranchId = null;
        $this->searchQuery = '';
        $this->quickFilter = 'all';
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportExcel()),
            Action::make('refresh')
                ->label('Segarkan Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => Notification::make()->title('Data Berhasil Diperbarui')->success()->send()),
        ];
    }

    /**
     * Mengambil data seluruh karyawan aktif dan kalkulasi missed check-in 7 hari terakhir
     * Menggunakan DB Query Builder untuk konsumsi memori yang sangat hemat (< 15MB untuk ribuan karyawan).
     */
    public function getCalculatedData(): array
    {
        @ini_set('memory_limit', '512M');

        $today = Carbon::today('Asia/Jakarta');
        $todayStr = $today->format('Y-m-d');
        $sevenDaysAgo = $today->copy()->subDays(6);
        $sevenDaysAgoStr = $sevenDaysAgo->format('Y-m-d');

        // Query raw lightweight stdClass employees
        $employees = DB::table('employees')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('companies', 'employees.company_id', '=', 'companies.id')
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at')
            ->where(function ($q) {
                $q->whereNull('employees.employment_status')
                  ->orWhere('employees.employment_status', '!=', 'resigned');
            })
            ->select([
                'employees.id',
                'employees.employee_no',
                'employees.full_name',
                'employees.photo',
                'employees.phone',
                'employees.principal_id',
                'employees.branch_id',
                'principals.name as principal_name',
                'branches.name as branch_name',
                'positions.name as position_name',
                'companies.name as company_name',
            ])
            ->get();

        $empIds = $employees->pluck('id')->toArray();

        if (empty($empIds)) {
            return [
                'today_formatted' => $today->translatedFormat('d F Y'),
                'seven_days_range' => $sevenDaysAgo->translatedFormat('d M') . ' - ' . $today->translatedFormat('d M Y'),
                'total_active_employees' => 0,
                'total_unchecked_employees' => 0,
                'employees' => [],
            ];
        }

        // 1. Ambil seluruh presensi 7 hari terakhir
        $attendances7Days = DB::table('attendances')
            ->whereIn('employee_id', $empIds)
            ->whereBetween('attendance_date', [$sevenDaysAgoStr, $todayStr])
            ->select('employee_id', 'attendance_date')
            ->get()
            ->groupBy('employee_id');

        // 2. Ambil seluruh permohonan cuti approved 7 hari terakhir
        $leaves7Days = DB::table('leave_requests')
            ->whereIn('employee_id', $empIds)
            ->where('status', 'approved')
            ->where('end_date', '>=', $sevenDaysAgoStr)
            ->where('start_date', '<=', $todayStr)
            ->select('employee_id', 'start_date', 'end_date')
            ->get()
            ->groupBy('employee_id');

        // 3. Ambil tanggal presensi terakhir
        $latestDates = DB::table('attendances')
            ->whereIn('employee_id', $empIds)
            ->selectRaw('employee_id, MAX(attendance_date) as max_date')
            ->groupBy('employee_id')
            ->pluck('max_date', 'employee_id');

        $uncheckedEmployees = [];

        foreach ($employees as $emp) {
            $empAttendances = $attendances7Days->get($emp->id, collect());
            $empLeaves = $leaves7Days->get($emp->id, collect());

            $attendedDates = $empAttendances->pluck('attendance_date')
                ->map(fn ($d) => is_string($d) ? substr($d, 0, 10) : Carbon::parse($d)->toDateString())
                ->toArray();

            // Hitung tanggal tidak hadir dalam 7 hari terakhir
            $missedDates = [];
            $curr = $sevenDaysAgo->copy();
            while ($curr <= $today) {
                $cStr = $curr->toDateString();
                $isAttended = in_array($cStr, $attendedDates);
                $isOnLeave = $empLeaves->first(function ($leave) use ($cStr) {
                    $s = is_string($leave->start_date) ? substr($leave->start_date, 0, 10) : Carbon::parse($leave->start_date)->toDateString();
                    $e = is_string($leave->end_date) ? substr($leave->end_date, 0, 10) : Carbon::parse($leave->end_date)->toDateString();
                    return $cStr >= $s && $cStr <= $e;
                }) !== null;

                if (!$isAttended && !$isOnLeave) {
                    $missedDates[] = [
                        'date' => $cStr,
                        'formatted_date' => $curr->translatedFormat('d M'),
                        'full_date' => $curr->translatedFormat('d M Y'),
                        'day_name' => $curr->translatedFormat('l'),
                        'is_today' => ($cStr === $todayStr),
                    ];
                }
                $curr->addDay();
            }

            $missedDates = array_reverse($missedDates);
            $missedCount = count($missedDates);

            // Hanya proses karyawan yang memiliki setidaknya 1 hari bolos/belum absen dalam 7 hari
            if ($missedCount > 0) {
                $lastAttDate = $latestDates->get($emp->id);
                $daysSinceLast = -1;
                $formattedLastAtt = 'Belum Pernah Hadir';
                
                if ($lastAttDate) {
                    try {
                        $parsedLast = Carbon::parse($lastAttDate);
                        $daysSinceLast = $parsedLast->diffInDays($today);
                        $formattedLastAtt = $parsedLast->translatedFormat('d M Y');
                    } catch (\Exception $e) {
                        $formattedLastAtt = (string)$lastAttDate;
                    }
                }

                $isTodayUnchecked = !in_array($todayStr, $attendedDates);

                $principalName = $emp->principal_name ?: ($emp->company_name ?: 'Tanpa Prinsiple');
                $branchName = $emp->branch_name ?: 'Tanpa Area';

                $uncheckedEmployees[] = [
                    'id' => $emp->id,
                    'employee_no' => $emp->employee_no ?? '-',
                    'full_name' => $emp->full_name ?? 'Unknown',
                    'photo' => $emp->photo,
                    'position' => $emp->position_name ?? 'Staff',
                    'principal_id' => $emp->principal_id ? (string)$emp->principal_id : null,
                    'principal_name' => $principalName,
                    'branch_id' => $emp->branch_id ? (string)$emp->branch_id : null,
                    'branch_name' => $branchName,
                    'phone' => $emp->phone ?? '-',
                    'is_today_unchecked' => $isTodayUnchecked,
                    'days_since_last' => $daysSinceLast,
                    'last_attendance_date' => $formattedLastAtt,
                    'missed_count_7days' => $missedCount,
                    'missed_dates' => $missedDates,
                ];
            }
        }

        return [
            'today_formatted' => $today->translatedFormat('d F Y'),
            'seven_days_range' => $sevenDaysAgo->translatedFormat('d M') . ' - ' . $today->translatedFormat('d M Y'),
            'total_active_employees' => $employees->count(),
            'total_unchecked_employees' => count($uncheckedEmployees),
            'employees' => $uncheckedEmployees,
        ];
    }

    /**
     * Membangun Pivot Matrix (Gambar 1): Rows = Prinsiple, Columns = Area
     */
    public function getMatrixData(): array
    {
        $calculated = $this->getCalculatedData();
        $employees = $calculated['employees'];

        // Ambil list Principals & Branches dari database secara ringan
        $principalsQuery = DB::table('principals')->orderBy('name');
        if (!empty($this->selectedPrincipalId)) {
            $principalsQuery->where('id', $this->selectedPrincipalId);
        }
        $principals = $principalsQuery->select('id', 'name')->get();

        $branchesQuery = DB::table('branches')->whereNull('deleted_at')->orderBy('name');
        if (!empty($this->selectedBranchId)) {
            $branchesQuery->where('id', $this->selectedBranchId);
        }
        $branches = $branchesQuery->select('id', 'name')->get();

        $columns = [];
        foreach ($branches as $branch) {
            $columns[(string)$branch->id] = $branch->name;
        }

        // Tambahkan jika ada branch di data yang tidak ada di master
        foreach ($employees as $emp) {
            if ($emp['branch_id'] && !isset($columns[$emp['branch_id']]) && empty($this->selectedBranchId)) {
                $columns[$emp['branch_id']] = $emp['branch_name'];
            }
        }

        $rows = [];
        $columnTotals = array_fill_keys(array_keys($columns), 0);
        $grandTotal = 0;

        foreach ($principals as $p) {
            $rowBranches = array_fill_keys(array_keys($columns), 0);
            $totalRow = 0;

            foreach ($employees as $emp) {
                if ($emp['principal_id'] == $p->id) {
                    $bId = (string)$emp['branch_id'];
                    if ($bId && isset($rowBranches[$bId])) {
                        $rowBranches[$bId]++;
                        $totalRow++;
                    }
                }
            }

            foreach ($rowBranches as $bId => $count) {
                $columnTotals[$bId] += $count;
            }
            $grandTotal += $totalRow;

            $rows[] = [
                'principal_id' => (string)$p->id,
                'principal_name' => $p->name,
                'branches' => $rowBranches,
                'total_row' => $totalRow,
            ];
        }

        // Handle employees without principal if any
        $noPrincipalEmployees = array_filter($employees, fn($e) => empty($e['principal_id']));
        if (!empty($noPrincipalEmployees) && empty($this->selectedPrincipalId)) {
            $rowBranches = array_fill_keys(array_keys($columns), 0);
            $totalRow = 0;
            foreach ($noPrincipalEmployees as $emp) {
                $bId = (string)$emp['branch_id'];
                if ($bId && isset($rowBranches[$bId])) {
                    $rowBranches[$bId]++;
                    $totalRow++;
                }
            }
            foreach ($rowBranches as $bId => $count) {
                $columnTotals[$bId] += $count;
            }
            $grandTotal += $totalRow;

            $rows[] = [
                'principal_id' => '0',
                'principal_name' => 'Lainnya / Tanpa Prinsiple',
                'branches' => $rowBranches,
                'total_row' => $totalRow,
            ];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
            'summary_info' => $calculated,
        ];
    }

    /**
     * Mengambil Detail Karyawan (Gambar 2) yang sudah terfilter
     */
    public function getFilteredDetailData(): array
    {
        $calculated = $this->getCalculatedData();
        $employees = $calculated['employees'];

        $pFilter = !empty($this->selectedCellPrincipalId) ? $this->selectedCellPrincipalId : $this->selectedPrincipalId;
        $bFilter = !empty($this->selectedCellBranchId) ? $this->selectedCellBranchId : $this->selectedBranchId;

        $filtered = array_filter($employees, function ($emp) use ($pFilter, $bFilter) {
            // Filter Principal
            if (!empty($pFilter)) {
                if ($pFilter === '0' && !empty($emp['principal_id'])) {
                    return false;
                }
                if ($pFilter !== '0' && $emp['principal_id'] != $pFilter) {
                    return false;
                }
            }

            // Filter Branch / Area
            if (!empty($bFilter)) {
                if ($emp['branch_id'] != $bFilter) {
                    return false;
                }
            }

            // Filter Quick Status
            if ($this->quickFilter === 'today' && !$emp['is_today_unchecked']) {
                return false;
            }
            if ($this->quickFilter === 'ge3' && $emp['missed_count_7days'] < 3) {
                return false;
            }
            if ($this->quickFilter === 'never' && $emp['days_since_last'] !== -1) {
                return false;
            }

            // Filter Search Text
            if (!empty(trim($this->searchQuery ?? ''))) {
                $q = strtolower(trim($this->searchQuery));
                $nameMatch = str_contains(strtolower($emp['full_name']), $q);
                $noMatch = str_contains(strtolower($emp['employee_no']), $q);
                $posMatch = str_contains(strtolower($emp['position']), $q);
                $pNameMatch = str_contains(strtolower($emp['principal_name']), $q);
                $bNameMatch = str_contains(strtolower($emp['branch_name']), $q);

                if (!$nameMatch && !$noMatch && !$posMatch && !$pNameMatch && !$bNameMatch) {
                    return false;
                }
            }

            return true;
        });

        // Urutkan: Karyawan dengan hari bolos terbanyak terlebih dahulu, lalu nama
        usort($filtered, function ($a, $b) {
            if ($a['missed_count_7days'] === $b['missed_count_7days']) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            return $b['missed_count_7days'] <=> $a['missed_count_7days'];
        });

        return array_values($filtered);
    }

    /**
     * Memilih Cell Matriks untuk filtering langsung tabel detail
     */
    public function selectMatrixCell($principalId = null, $branchId = null, $principalName = null, $branchName = null): void
    {
        $pId = (!is_null($principalId) && $principalId !== '' && $principalId !== '0') ? (string)$principalId : null;
        $bId = (!is_null($branchId) && $branchId !== '' && $branchId !== '0') ? (string)$branchId : null;
        $pName = !empty($principalName) ? (string)$principalName : null;
        $bName = !empty($branchName) ? (string)$branchName : null;

        if ($this->selectedCellPrincipalId === $pId && $this->selectedCellBranchId === $bId) {
            $this->resetCellFilter();
            return;
        }

        $this->selectedCellPrincipalId = $pId;
        $this->selectedCellBranchId = $bId;
        $this->selectedCellPrincipalName = $pName;
        $this->selectedCellBranchName = $bName;

        Notification::make()
            ->title('Filter Matriks Diterapkan')
            ->body("Menampilkan detail untuk: " . ($pName ?: 'Semua Prinsiple') . " - " . ($bName ?: 'Semua Area'))
            ->info()
            ->send();
    }

    /**
     * Reset filter cell matriks
     */
    public function resetCellFilter(): void
    {
        $this->selectedCellPrincipalId = null;
        $this->selectedCellBranchId = null;
        $this->selectedCellPrincipalName = null;
        $this->selectedCellBranchName = null;
    }

    /**
     * Reset semua filter ke kondisi awal
     */
    public function resetAllFilters(): void
    {
        $this->selectedPrincipalId = null;
        $this->selectedBranchId = null;
        $this->searchQuery = '';
        $this->quickFilter = 'all';
        $this->resetCellFilter();

        Notification::make()
            ->title('Filter Direset')
            ->body('Semua data ditampilkan kembali.')
            ->success()
            ->send();
    }

    /**
     * Export hasil monitoring ke Excel
     */
    public function exportExcel()
    {
        $details = $this->getFilteredDetailData();
        $today = Carbon::today('Asia/Jakarta')->translatedFormat('d F Y');

        $rows = [];
        $no = 1;
        foreach ($details as $row) {
            $missedDatesStr = collect($row['missed_dates'])->pluck('formatted_date')->implode(', ');

            $rows[] = [
                $no++,
                $row['full_name'],
                $row['employee_no'],
                $row['position'],
                $row['principal_name'],
                $row['branch_name'],
                $row['missed_count_7days'] . ' Hari',
                $missedDatesStr,
                $row['last_attendance_date'],
            ];
        }

        $fileName = 'Monitoring_Tim_Belum_CheckIn_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new TeamUncheckedExport($rows, $today), $fileName);
    }
}
