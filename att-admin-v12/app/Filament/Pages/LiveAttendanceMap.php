<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Principal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class LiveAttendanceMap extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance & Time Management';
    protected static ?string $navigationLabel = 'Live Map Presensi';
    protected static ?string $title = 'Live Map Monitoring Presensi Karyawan';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.live-attendance-map';

    // Filters
    public ?string $selectedPrincipalId = null;
    public ?string $selectedBranchId = null;
    public string $sourceFilter = 'all'; // 'all', 'live', 'checkin'
    public string $searchQuery = '';
    public bool $autoRefresh = false;
    public int $refreshInterval = 30; // seconds

    // Memoized cache per request
    protected ?array $memoizedData = null;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    /**
     * Khusus akses Administrator saja
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdministrator')) {
            return $user->isAdministrator();
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['Super Admin', 'super_admin', 'Administrator', 'Admin', 'admin'])) {
            return true;
        }

        return in_array(strtolower($user->role ?? ''), ['admin', 'superadmin', 'administrator']);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403, 'Akses ditolak. Halaman Live Map Monitoring khusus untuk Administrator.');

        $this->selectedPrincipalId = null;
        $this->selectedBranchId = null;
        $this->sourceFilter = 'all';
        $this->searchQuery = '';
        $this->autoRefresh = false;
        $this->refreshInterval = 30;
    }

    public function updatedSelectedPrincipalId(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    public function updatedSelectedBranchId(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    public function updatedSourceFilter(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    public function updatedSearchQuery(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    public function refreshData(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();

        Notification::make()
            ->title('Peta Berhasil Disinkronkan')
            ->body('Data titik presensi dan live tracking karyawan berhasil diperbarui.')
            ->success()
            ->send();
    }

    public function fetchLatestCoordinates(): void
    {
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    public function resetFilters(): void
    {
        $this->selectedPrincipalId = null;
        $this->selectedBranchId = null;
        $this->sourceFilter = 'all';
        $this->searchQuery = '';
        $this->memoizedData = null;
        $this->dispatchMapUpdate();
    }

    protected function dispatchMapUpdate(): void
    {
        $data = $this->getMapData();
        $this->dispatch('map-data-updated', [
            'employees' => $data['employees'],
            'unmapped' => $data['unmapped'],
            'summary' => $data['summary'],
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fitBounds')
                ->label('Pusatkan Semua')
                ->icon('heroicon-o-viewfinder-circle')
                ->color('gray')
                ->extraAttributes(['id' => 'btn-fit-bounds-action']),
            Action::make('refresh')
                ->label('Segarkan Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshData()),
        ];
    }

    /**
     * Mengambil seluruh data karyawan yang sedang check-in beserta koordinat terakhirnya.
     */
    public function getMapData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        @ini_set('memory_limit', '512M');

        $now = Carbon::now('Asia/Jakarta');
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        // 1. Query seluruh sesi kehadiran yang sedang aktif (checkin_at IS NOT NULL, checkout_at IS NULL)
        $query = DB::table('attendances')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->leftJoin('principals', 'employees.principal_id', '=', 'principals.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('areas', 'employees.area_id', '=', 'areas.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->leftJoin('companies', 'employees.company_id', '=', 'companies.id')
            ->leftJoin('attendance_logs as checkin_log', 'attendances.checkin_log_id', '=', 'checkin_log.id')
            ->leftJoin('employee_schedules', 'attendances.employee_schedule_id', '=', 'employee_schedules.id')
            ->leftJoin('work_locations', 'employee_schedules.work_location_id', '=', 'work_locations.id')
            ->whereNull('attendances.checkout_at')
            ->whereNotNull('attendances.checkin_at')
            ->where(function ($q) use ($todayStr, $now) {
                $q->where('attendances.attendance_date', $todayStr)
                  ->orWhere('attendances.checkin_at', '>=', $now->copy()->subHours(24));
            })
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at')
            ->where(function ($q) {
                $q->whereNull('employees.employment_status')
                  ->orWhere('employees.employment_status', '!=', 'resigned');
            });

        // Filter Principal
        if (!empty($this->selectedPrincipalId)) {
            $query->where('employees.principal_id', $this->selectedPrincipalId);
        }

        // Filter Area / Cabang
        if (!empty($this->selectedBranchId)) {
            $query->where(function ($q) {
                $q->where('employees.branch_id', $this->selectedBranchId)
                  ->orWhere('employees.area_id', $this->selectedBranchId);
            });
        }

        // Filter Search Query
        if (!empty(trim($this->searchQuery))) {
            $term = '%' . trim($this->searchQuery) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('employees.full_name', 'like', $term)
                  ->orWhere('employees.employee_no', 'like', $term)
                  ->orWhere('positions.name', 'like', $term);
            });
        }

        $records = $query->select([
            'attendances.id as attendance_id',
            'attendances.attendance_date',
            'attendances.checkin_at',
            'attendances.status as attendance_status',
            'attendances.checkin_log_id',
            'employees.id as employee_id',
            'employees.full_name',
            'employees.employee_no',
            'employees.photo',
            'employees.company_id',
            'employees.principal_id',
            'employees.branch_id',
            'employees.area_id',
            'employees.phone',
            'principals.name as principal_name',
            'branches.name as branch_name',
            'areas.name as area_name',
            'positions.name as position_name',
            'companies.name as company_name',
            'checkin_log.latitude as checkin_lat',
            'checkin_log.longitude as checkin_lng',
            'checkin_log.address_text as checkin_address',
            'checkin_log.photo_path as checkin_photo',
            'checkin_log.logged_at as checkin_logged_at',
            'work_locations.name as scheduled_location_name',
            'work_locations.latitude as scheduled_lat',
            'work_locations.longitude as scheduled_lng',
            'work_locations.address as scheduled_address',
        ])
        ->orderByDesc('attendances.checkin_at')
        ->get();

        // 2. Query titik tracking terbaru untuk seluruh sesi kehadiran aktif
        $attendanceIds = $records->pluck('attendance_id')->toArray();
        $latestTracking = [];

        if (!empty($attendanceIds)) {
            $maxTrackingIds = DB::table('tracking_histories')
                ->whereIn('attendance_id', $attendanceIds)
                ->selectRaw('attendance_id, MAX(id) as max_id')
                ->groupBy('attendance_id')
                ->pluck('max_id', 'attendance_id');

            if ($maxTrackingIds->isNotEmpty()) {
                $latestTracking = DB::table('tracking_histories')
                    ->whereIn('id', $maxTrackingIds->values())
                    ->select(['id', 'attendance_id', 'latitude', 'longitude', 'created_at'])
                    ->get()
                    ->keyBy('attendance_id');
            }
        }

        $palette = ['#4f46e5', '#0284c7', '#0d9488', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#db2777', '#2563eb', '#0891b2', '#059669', '#ea580c'];

        $mappedEmployees = [];
        $unmappedEmployees = [];
        $totalLiveGps = 0;
        $totalCheckinPoint = 0;

        foreach ($records as $rec) {
            $lat = null;
            $lng = null;
            $source = 'none';
            $sourceLabel = 'Tidak Tersedia';
            $lastUpdateRaw = null;
            $locationName = null;

            // Prioritas 1: Tracking History terbaru (GPS real-time)
            if (isset($latestTracking[$rec->attendance_id])) {
                $th = $latestTracking[$rec->attendance_id];
                $tLat = (float) $th->latitude;
                $tLng = (float) $th->longitude;
                if ($this->isValidCoordinate($tLat, $tLng)) {
                    $lat = $tLat;
                    $lng = $tLng;
                    $source = 'live_tracking';
                    $sourceLabel = 'GPS Live Tracking';
                    $lastUpdateRaw = $th->created_at;
                }
            }

            // Prioritas 2: Titik Check-in (Attendance Log)
            if ($lat === null && $rec->checkin_lat !== null && $rec->checkin_lng !== null) {
                $cLat = (float) $rec->checkin_lat;
                $cLng = (float) $rec->checkin_lng;
                if ($this->isValidCoordinate($cLat, $cLng)) {
                    $lat = $cLat;
                    $lng = $cLng;
                    $source = 'checkin_point';
                    $sourceLabel = 'Titik Check-in';
                    $lastUpdateRaw = $rec->checkin_logged_at ?? $rec->checkin_at;
                    $locationName = $rec->checkin_address;
                }
            }

            // Prioritas 3: Titik Toko Jadwal Karyawan
            if ($lat === null && $rec->scheduled_lat !== null && $rec->scheduled_lng !== null) {
                $sLat = (float) $rec->scheduled_lat;
                $sLng = (float) $rec->scheduled_lng;
                if ($this->isValidCoordinate($sLat, $sLng)) {
                    $lat = $sLat;
                    $lng = $sLng;
                    $source = 'scheduled_location';
                    $sourceLabel = 'Lokasi Terjadwal';
                    $lastUpdateRaw = $rec->checkin_at;
                    $locationName = $rec->scheduled_location_name;
                }
            }

            // Foto URL Profil
            $photoUrl = null;
            if (!empty($rec->photo)) {
                if (str_starts_with($rec->photo, 'http://') || str_starts_with($rec->photo, 'https://')) {
                    $photoUrl = $rec->photo;
                } else {
                    $domainMap = [
                        1 => 'amk.esa-solutions.id',
                        2 => 'akp.esa-solutions.id',
                        3 => 'atk.esa-solutions.id',
                    ];
                    $domain = $domainMap[$rec->company_id] ?? null;
                    if (!$domain) {
                        $host = request()?->getHost();
                        if ($host && !in_array($host, ['localhost', '127.0.0.1'])) {
                            $domain = $host;
                        } else {
                            $domain = 'appsend.my.id';
                        }
                    }
                    $photoUrl = 'https://' . $domain . '/storage/' . ltrim($rec->photo, '/');
                }
                $photoUrl = str_replace('esa-solution.id', 'esa-solutions.id', $photoUrl);
            }

            // Inisial Nama (2 Huruf)
            $words = preg_split('/\s+/', trim($rec->full_name));
            if (count($words) >= 2) {
                $initials = mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
            } elseif (count($words) === 1 && !empty($words[0])) {
                $initials = mb_substr($words[0], 0, 2);
            } else {
                $initials = 'EM';
            }
            $initials = strtoupper($initials);

            $avatarColor = $palette[abs(crc32($rec->full_name)) % count($palette)];

            // Status Keaktifan Update GPS (30 menit terakhir)
            $isLive = false;
            $lastUpdateFormatted = '-';
            $lastUpdateDiff = '-';
            if ($lastUpdateRaw) {
                try {
                    $dt = Carbon::parse($lastUpdateRaw)->timezone('Asia/Jakarta');
                    $lastUpdateFormatted = $dt->format('H:i:s') . ' WIB';
                    $lastUpdateDiff = $dt->diffForHumans();
                    if ($source === 'live_tracking' && $dt->diffInMinutes($now) <= 30) {
                        $isLive = true;
                    }
                } catch (\Exception $e) {
                    $lastUpdateFormatted = (string) $lastUpdateRaw;
                }
            }

            // Format Jam Check-in
            $checkinFormatted = '-';
            if ($rec->checkin_at) {
                try {
                    $checkinFormatted = Carbon::parse($rec->checkin_at)->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB';
                } catch (\Exception $e) {
                    $checkinFormatted = (string) $rec->checkin_at;
                }
            }

            $empData = [
                'id' => $rec->employee_id,
                'attendance_id' => $rec->attendance_id,
                'name' => $rec->full_name,
                'employee_no' => $rec->employee_no ?? '-',
                'position' => $rec->position_name ?? 'Staf Lapangan',
                'principal' => $rec->principal_name ?? 'Umum',
                'branch' => $rec->branch_name ?? $rec->area_name ?? 'Pusat',
                'phone' => $rec->phone ?? '',
                'photo_url' => $photoUrl,
                'initials' => $initials,
                'avatar_color' => $avatarColor,
                'badge_color' => $isLive ? '#10b981' : ($source === 'checkin_point' ? '#3b82f6' : '#64748b'),
                'attendance_status' => $rec->attendance_status ?? 'present',
                'status_label' => match($rec->attendance_status) {
                    'present' => 'Hadir Tepat Waktu',
                    'late' => 'Terlambat',
                    default => ucfirst($rec->attendance_status ?? 'Hadir'),
                },
                'source' => $source,
                'source_label' => $sourceLabel,
                'is_live' => $isLive,
                'lat' => $lat,
                'lng' => $lng,
                'location_name' => $locationName ?? ($rec->scheduled_location_name ?? '-'),
                'checkin_time' => $checkinFormatted,
                'last_update' => $lastUpdateFormatted,
                'last_update_diff' => $lastUpdateDiff,
                'tracking_url' => url('/admin/attendances/' . $rec->attendance_id . '/view-route'),
                'google_maps_url' => ($lat && $lng) ? "https://maps.google.com/?q={$lat},{$lng}" : null,
            ];

            if ($lat !== null && $lng !== null) {
                if ($source === 'live_tracking') {
                    $totalLiveGps++;
                } else {
                    $totalCheckinPoint++;
                }

                // Filter jenis sumber koordinat
                if ($this->sourceFilter === 'live' && $source !== 'live_tracking') {
                    continue;
                }
                if ($this->sourceFilter === 'checkin' && $source !== 'checkin_point') {
                    continue;
                }

                $mappedEmployees[] = $empData;
            } else {
                $unmappedEmployees[] = $empData;
            }
        }

        return $this->memoizedData = [
            'employees' => $mappedEmployees,
            'unmapped' => $unmappedEmployees,
            'summary' => [
                'total_checked_in' => count($records),
                'total_mapped' => count($mappedEmployees),
                'total_unmapped' => count($unmappedEmployees),
                'total_live_gps' => $totalLiveGps,
                'total_checkin_point' => $totalCheckinPoint,
                'active_principals_count' => $records->pluck('principal_name')->filter()->unique()->count(),
                'active_branches_count' => $records->pluck('branch_name')->filter()->unique()->count(),
                'timestamp' => $now->format('H:i:s') . ' WIB',
            ],
        ];
    }

    /**
     * Memvalidasi bahwa koordinat berada di dalam rentang wilayah Indonesia dan tidak nol.
     */
    protected function isValidCoordinate(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        if ($lat == 0.0 && $lng == 0.0) return false;
        return ($lat >= -12.0 && $lat <= 7.0 && $lng >= 94.0 && $lng <= 142.0);
    }

    protected function getViewData(): array
    {
        $mapData = $this->getMapData();

        return [
            'allPrincipals' => Principal::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'allBranches' => Branch::orderBy('name')->get(['id', 'name']),
            'employees' => $mapData['employees'],
            'unmapped' => $mapData['unmapped'],
            'summary' => $mapData['summary'],
        ];
    }
}
