@php
    $formattedDate = \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y');
    
    // Status Presensi
    $status = $attendance?->status;
    $statusLabel = match($status) {
        'present'   => 'Hadir Tepat Waktu',
        'late'      => 'Terlambat',
        'absent'    => 'Tidak Hadir (Alpha)',
        'leave'     => 'Cuti / Izin',
        'sick'      => 'Sakit',
        'permit'    => 'Izin Khusus',
        'half_day'  => 'Setengah Hari',
        default     => $status ? ucfirst($status) : ($leaveRequest ? ucfirst($leaveRequest->type) : 'Belum Ada Data'),
    };
    
    $statusColor = match($status) {
        'present'   => 'emerald',
        'late'      => 'amber',
        'absent'    => 'rose',
        'leave', 'sick', 'permit' => 'indigo',
        default     => 'gray',
    };

    // Jam Check-in & Check-out
    $checkinTime = $attendance?->checkin_at 
        ? \Carbon\Carbon::parse($attendance->checkin_at)->timezone('Asia/Jakarta')->format('H:i:s') 
        : null;
    $checkoutTime = $attendance?->checkout_at 
        ? \Carbon\Carbon::parse($attendance->checkout_at)->timezone('Asia/Jakarta')->format('H:i:s') 
        : null;

    // Hitung Durasi Kerja
    $workDuration = null;
    if ($attendance?->checkin_at) {
        $start = \Carbon\Carbon::parse($attendance->checkin_at);
        $end = $attendance->checkout_at ? \Carbon\Carbon::parse($attendance->checkout_at) : null;
        if ($end) {
            $diffMinutes = $start->diffInMinutes($end);
            $hours = floor($diffMinutes / 60);
            $minutes = $diffMinutes % 60;
            $workDuration = ($hours > 0 ? "{$hours} Jam " : "") . "{$minutes} Menit";
        } elseif (\Carbon\Carbon::parse($date)->isToday()) {
            $workDuration = 'Sedang Bekerja';
        }
    }

    // Shift & Lokasi Terjadwal
    $shiftName = $schedule?->shift?->name ?? $attendance?->employeeSchedule?->shift?->name ?? 'OFFICE';
    $shiftTime = null;
    if ($schedule?->shift?->start_time && $schedule?->shift?->end_time) {
        $shiftTime = substr($schedule->shift->start_time, 0, 5) . ' - ' . substr($schedule->shift->end_time, 0, 5);
    } elseif ($attendance?->employeeSchedule?->shift?->start_time) {
        $shiftTime = substr($attendance->employeeSchedule->shift->start_time, 0, 5) . ' - ' . substr($attendance->employeeSchedule->shift->end_time, 0, 5);
    }

    $scheduledLocation = $schedule?->workLocation?->name 
        ?? $attendance?->employeeSchedule?->workLocation?->name 
        ?? ($employee?->branch?->name ?? 'Belum Ditentukan');
    
    $companyName = $schedule?->workLocation?->company?->name 
        ?? $employee?->principal?->name 
        ?? $employee?->company?->name;
@endphp

<div class="space-y-6 text-gray-800 dark:text-gray-200">
    {{-- Header Profile Karyawan & Tanggal --}}
    <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-md relative overflow-hidden border border-indigo-900/50">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-indigo-600/30 ring-2 ring-white/20 flex-shrink-0">
                    @if($employee)
                        {{ strtoupper(substr($employee->full_name, 0, 2)) }}
                    @else
                        Presensi
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl font-bold text-white tracking-tight">
                            {{ $employee?->full_name ?? 'Karyawan' }}
                        </h2>
                        @if($employee?->employee_no)
                            <span class="px-2.5 py-0.5 text-xs font-semibold bg-white/10 text-indigo-200 rounded-full border border-white/15">
                                NIK: {{ $employee->employee_no }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 mt-1.5 text-xs text-indigo-200/90 flex-wrap">
                        @if($employee?->department?->name)
                            <span class="font-medium text-white/90">{{ $employee->department->name }}</span>
                            <span class="text-indigo-400">•</span>
                        @endif
                        @if($employee?->position?->name)
                            <span>{{ $employee->position->name }}</span>
                            <span class="text-indigo-400">•</span>
                        @endif
                        @if($employee?->branch?->name)
                            <span>{{ $employee->branch->name }}</span>
                        @endif
                        @if($companyName)
                            <span class="text-indigo-400">•</span>
                            <span class="text-amber-300 font-medium">{{ $companyName }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sm:text-right flex-shrink-0 bg-white/10 sm:bg-transparent p-3 sm:p-0 rounded-xl backdrop-blur-sm sm:backdrop-blur-none border border-white/10 sm:border-0">
                <div class="text-xs uppercase tracking-wider text-indigo-300 font-semibold">Tanggal Absensi</div>
                <div class="text-base font-bold text-white mt-0.5">{{ $formattedDate }}</div>
            </div>
        </div>
    </div>

    {{-- Notifikasi Penyesuaian Manual / Import Excel --}}
    @if($attendance && $attendance->is_manual_correction)
        <div class="p-4 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/80 rounded-2xl flex items-start gap-3.5 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/60 flex items-center justify-center text-purple-600 dark:text-purple-300 text-base flex-shrink-0">
                ⚡
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-wider text-purple-800 dark:text-purple-300">Data Hasil Penyesuaian / Import Excel</div>
                <div class="text-sm text-purple-900 dark:text-purple-200 mt-0.5">
                    <strong>Catatan:</strong> {{ $attendance->correction_note ?: 'Disinkronkan melalui Import Excel oleh Admin' }}
                </div>
            </div>
        </div>
    @endif

    {{-- 3 Kartu Ringkasan Informasi (Status Presensi, Jadwal Shift, GPS & Live Tracking) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Card 1: Status Presensi & Jam --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-800/90 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status Kehadiran</span>
                    @if($status === 'present')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ $statusLabel }}
                        </span>
                    @elseif($status === 'late')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            {{ $statusLabel }}
                            @if($attendance?->late_minutes > 0)
                                (+{{ $attendance->late_minutes }}m)
                            @endif
                        </span>
                    @elseif($status === 'absent')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            {{ $statusLabel }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            {{ $statusLabel }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-arrow-right-end-on-rectangle" class="w-4 h-4 text-emerald-500" />
                            <span>Check-In</span>
                        </div>
                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ $checkinTime ?? '--:--:--' }}
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-arrow-left-start-on-rectangle" class="w-4 h-4 text-amber-500" />
                            <span>Check-Out</span>
                        </div>
                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ $checkoutTime ?? '--:--:--' }}
                        </div>
                    </div>
                </div>
            </div>

            @if($workDuration)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Durasi Kerja:</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $workDuration }}</span>
                </div>
            @endif
        </div>

        {{-- Card 2: Jadwal & Shift Roster --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-800/90 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jadwal Shift Roster</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        {{ $schedule?->schedule_type ? ucfirst($schedule->schedule_type) : 'Workday' }}
                    </span>
                </div>

                <div class="space-y-3 mt-4">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5">
                            <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Shift Kerja</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $shiftName }} 
                                @if($shiftTime)
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $shiftTime }})</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5">
                            <x-filament::icon icon="heroicon-o-building-office-2" class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Lokasi Penempatan</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white truncate" title="{{ $scheduledLocation }}">
                                {{ $scheduledLocation }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($schedule?->planned_start_at)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Target Jam Masuk:</span>
                    <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($schedule->planned_start_at)->format('H:i') }} WIB</span>
                </div>
            @endif
        </div>

        {{-- Card 3: GPS & Live Tracking --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-800/90 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">GPS & Live Tracking</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        {{ $trackingCount ?? 0 }} Titik GPS
                    </span>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">
                    Sistem merekam pergerakan posisi karyawan secara otomatis saat aplikasi aktif dan berada dalam jam kerja.
                </p>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                @if($attendance)
                    <a
                        href="{{ \App\Filament\Resources\Attendances\AttendanceResource::getUrl('view-route', ['record' => $attendance->id]) }}"
                        target="_blank"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white text-xs font-bold shadow-md shadow-sky-500/20 transition-all transform active:scale-98"
                    >
                        <x-filament::icon icon="heroicon-o-map" class="w-4 h-4" />
                        <span>Lihat Rute Live Tracking (Peta Lengkap)</span>
                    </a>
                @else
                    <button disabled class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-400 text-xs font-bold cursor-not-allowed border border-gray-200 dark:border-gray-700">
                        <x-filament::icon icon="heroicon-o-map" class="w-4 h-4" />
                        <span>Tracking Tidak Tersedia</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Bagian Log Aktivitas & Bukti Presensi (Activity Logs) --}}
    <div class="border-t border-gray-200/80 dark:border-gray-700/80 pt-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Log Aktivitas & Bukti Lokasi</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Detail seluruh interaksi check-in, visit, dan checkout pada hari ini.</p>
                </div>
            </div>
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                Total: {{ count($logs ?? []) }} Log
            </span>
        </div>
        
        @if (empty($logs) || count($logs) === 0)
            <div class="p-8 bg-gray-50/70 dark:bg-gray-800/40 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-400">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6" />
                </div>
                <div class="text-sm font-bold text-gray-700 dark:text-gray-300">Tidak Ada Log Aktivitas</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    Karyawan tidak memiliki catatan aktivitas check-in, check-out, ataupun kunjungan pada tanggal {{ $formattedDate }}.
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($logs as $index => $log)
                    @php
                        $logTypeLabel = match($log->log_type) {
                            'checkin'       => 'Check In Presensi',
                            'checkout'      => 'Check Out Presensi',
                            'visit_in'      => 'Visit In (Mulai Kunjungan)',
                            'visit_out'     => 'Visit Out (Selesai Kunjungan)',
                            'visit_report'  => 'Laporan Kunjungan (Visit Report)',
                            default         => str_replace('_', ' ', ucfirst($log->log_type)),
                        };
                        
                        $isCheckin = in_array($log->log_type, ['checkin', 'visit_in']);
                        $isCheckout = in_array($log->log_type, ['checkout', 'visit_out']);
                        $isReport  = $log->log_type === 'visit_report';

                        $badgeColorClass = match(true) {
                            $log->log_type === 'checkin'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                            $log->log_type === 'checkout' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                            $log->log_type === 'visit_in' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                            $log->log_type === 'visit_out'=> 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                            default                       => 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                        };

                        $itItem = $log->itinerary_item_id ? \App\Models\ItineraryItem::with('workLocation')->find($log->itinerary_item_id) : null;
                        $locationName = $log->address_text 
                            ?: ($itItem?->workLocation?->name 
                            ?: ($log->latitude && $log->longitude ? number_format($log->latitude, 6) . ', ' . number_format($log->longitude, 6) : 'Lokasi tidak terekam'));
                    @endphp

                    <div class="p-5 bg-white dark:bg-gray-800/95 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-sm transition-all hover:shadow-md">
                        {{-- Top Header Row of the Log --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 mb-4 border-b border-gray-100 dark:border-gray-700/60 gap-2">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase border {{ $badgeColorClass }}">
                                    {{ $logTypeLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-sm font-bold text-gray-900 dark:text-white font-mono">
                                    <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 text-gray-400" />
                                    {{ \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->format('H:i:s') }} WIB
                                </span>
                            </div>

                            {{-- Geofence Status Pill --}}
                            @if(!is_null($log->is_inside_geofence))
                                <div>
                                    @if($log->is_inside_geofence)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <x-filament::icon icon="heroicon-s-check-circle" class="w-3.5 h-3.5 text-emerald-500" />
                                            <span>Dalam Radius Kantor</span>
                                            @if($log->distance_from_location_meter)
                                                <span class="font-bold">({{ round($log->distance_from_location_meter) }}m)</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <x-filament::icon icon="heroicon-s-exclamation-triangle" class="w-3.5 h-3.5 text-rose-500" />
                                            <span>Di Luar Radius Kantor</span>
                                            @if($log->distance_from_location_meter)
                                                <span class="font-bold">({{ round($log->distance_from_location_meter) }}m)</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- 2-Column Content Layout (Left: Info & Foto, Right: Embedded Map) --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                            {{-- Left Side: Details, Photo, Notes (7 cols) --}}
                            <div class="lg:col-span-6 space-y-3.5">
                                {{-- Alamat & Koordinat --}}
                                <div class="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                                    <div class="flex items-start gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                        <x-filament::icon icon="heroicon-o-map-pin" class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" />
                                        <span>Lokasi Presensi:</span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white pl-6 leading-snug">
                                        {{ $locationName }}
                                    </div>

                                    @if($log->latitude && $log->longitude)
                                        <div class="mt-2.5 pt-2 border-t border-gray-200/50 dark:border-gray-700/50 pl-6 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-mono">
                                                {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                                                @if($log->accuracy_meter)
                                                    <span class="text-gray-400">(±{{ round($log->accuracy_meter) }}m)</span>
                                                @endif
                                            </span>
                                            <a 
                                                href="https://maps.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}" 
                                                target="_blank" 
                                                class="text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1 font-semibold"
                                            >
                                                <span>Buka Maps</span>
                                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3 h-3" />
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Catatan Log / Keterangan --}}
                                @if($log->note)
                                    <div class="p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-800/60 text-xs text-amber-900 dark:text-amber-200">
                                        <div class="font-bold flex items-center gap-1 mb-1">
                                            <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="w-3.5 h-3.5" />
                                            <span>Catatan:</span>
                                        </div>
                                        <div class="italic">"{{ $log->note }}"</div>
                                    </div>
                                @endif

                                {{-- Foto Selfie / Lampiran --}}
                                @if($log->photo_path)
                                    @php
                                        $photoUrl = \Illuminate\Support\Facades\Storage::url($log->photo_path);
                                    @endphp
                                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50/80 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
                                        <a href="{{ $photoUrl }}" target="_blank" class="block flex-shrink-0 group relative overflow-hidden rounded-xl shadow ring-1 ring-gray-200 dark:ring-gray-700">
                                            <img 
                                                src="{{ $photoUrl }}" 
                                                alt="Foto Presensi" 
                                                class="w-20 h-20 object-cover group-hover:scale-105 transition-transform duration-300"
                                            >
                                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="w-5 h-5" />
                                            </div>
                                        </a>
                                        <div>
                                            <div class="text-xs font-bold text-gray-900 dark:text-white">Foto Bukti Presensi</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Selfie kamera aktif saat verifikasi lokasi</div>
                                            <a href="{{ $photoUrl }}" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                <span>Lihat Resolusi Penuh</span>
                                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-3 h-3" />
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Right Side: Embedded Google Map (6 cols) --}}
                            <div class="lg:col-span-6">
                                @if ($log->latitude && $log->longitude)
                                    <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 shadow-inner bg-gray-100 dark:bg-gray-900 h-56">
                                        <iframe 
                                            width="100%" 
                                            height="100%" 
                                            style="border:0;" 
                                            loading="lazy" 
                                            allowfullscreen 
                                            src="https://maps.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}&z=15&output=embed">
                                        </iframe>
                                    </div>
                                @else
                                    <div class="h-56 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 flex flex-col items-center justify-center text-gray-400 text-xs">
                                        <x-filament::icon icon="heroicon-o-map-pin" class="w-8 h-8 mb-1.5 opacity-50" />
                                        <span>Koordinat GPS tidak tersedia</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
