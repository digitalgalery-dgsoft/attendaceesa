@extends('portal.layout')

@section('title', 'Live Tracking Rute GPS - ' . ($employee->full_name ?? 'Karyawan'))
@section('page_title', 'Live Tracking (GPS)')
@section('breadcrumb_active', 'Live Tracking')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    .tracking-page-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .tracking-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* 4 Metric Cards */
    .metric-grid-4 {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 14px;
    }
    @media (min-width: 640px) {
        .metric-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .metric-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }

    /* Map & Timeline Grid */
    .map-grid-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        align-items: start;
    }
    @media (min-width: 1024px) {
        .map-grid-container {
            grid-template-columns: 2fr 1fr;
        }
    }

    /* Custom Leaflet Marker Icon */
    .custom-div-icon {
        background: transparent;
        border: none;
    }

    .tracking-row-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .tracking-row-item:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        transform: translateX(2px);
    }

    .activity-checkpoint-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .activity-checkpoint-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
</style>
@endpush

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
        default     => $status ? ucfirst($status) : 'Belum Ada Data',
    };
    
    $statusBg = match($status) {
        'present'   => '#ecfdf5',
        'late'      => '#fffbeb',
        'absent'    => '#fff1f2',
        'leave', 'sick', 'permit' => '#eef2ff',
        default     => '#f1f5f9',
    };

    $statusColor = match($status) {
        'present'   => '#059669',
        'late'      => '#d97706',
        'absent'    => '#e11d48',
        'leave', 'sick', 'permit' => '#4f46e5',
        default     => '#64748b',
    };

    $statusBorder = match($status) {
        'present'   => '#a7f3d0',
        'late'      => '#fde68a',
        'absent'    => '#fecdd3',
        'leave', 'sick', 'permit' => '#c7d2fe',
        default     => '#cbd5e1',
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

    $empName = strtoupper($employee?->full_name ?? 'KARYAWAN');
    $initials = 'EM';
    if ($empName) {
        $parts = explode(' ', trim($empName));
        $initials = count($parts) > 1 ? substr($parts[0], 0, 1) . substr($parts[1], 0, 1) : substr($parts[0], 0, 2);
    }

    // Format Total Distance
    $formattedDistance = $totalDistanceMeter >= 1000 
        ? number_format($totalDistanceMeter / 1000, 2) . ' km' 
        : round($totalDistanceMeter) . ' meter';

    $firstPointTime = !empty($trackingHistories) ? $trackingHistories[0]['created_at'] : null;
    $lastPointTime  = !empty($trackingHistories) ? $trackingHistories[count($trackingHistories) - 1]['created_at'] : null;
@endphp

@section('content')
<div class="tracking-page-wrapper">
    {{-- Top Navigation Header --}}
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
        <div>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">
                <a href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id]) }}" style="color: #64748b; text-decoration: none;">Attendances</a> 
                &rsaquo; 
                <a href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id, 'start_date' => $date, 'end_date' => $date]) }}" style="color: #64748b; text-decoration: none;">Attendance Roster</a>
                &rsaquo; 
                <span style="color: #4f46e5;">Live Tracking (GPS)</span>
            </div>
            <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.5px;">
                Live Tracking Rute GPS
            </h1>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <a 
                href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id, 'start_date' => $date, 'end_date' => $date]) }}" 
                style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Attendance Roster
            </a>
            @if($firstPointTime && count($trackingHistories) > 0)
                <a 
                    href="https://maps.google.com/maps?q={{ $trackingHistories[0]['latitude'] }},{{ $trackingHistories[0]['longitude'] }}" 
                    target="_blank"
                    style="background: #10b981; color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Buka di Google Maps
                </a>
            @endif
        </div>
    </div>

    {{-- Header Banner Karyawan & Tanggal --}}
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); color: #ffffff; border-radius: 16px; padding: 20px 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; color: #ffffff; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); flex-shrink: 0;">
                {{ $initials }}
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; text-transform: uppercase;">
                        {{ $empName }}
                    </span>
                    @if($employee?->employee_no)
                        <span style="font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.15); color: #e0e7ff; padding: 2px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">
                            NIK: {{ $employee->employee_no }}
                        </span>
                    @endif
                </div>
                <div style="font-size: 12px; color: #c7d2fe; margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    @if($employee?->department?->name)
                        <span>{{ $employee->department->name }}</span>
                        <span style="opacity: 0.5;">•</span>
                    @endif
                    @if($employee?->position?->name)
                        <span>{{ $employee->position->name }}</span>
                        <span style="opacity: 0.5;">•</span>
                    @endif
                    @if($employee?->branch?->name)
                        <span>{{ $employee->branch->name }}</span>
                    @endif
                    @if($companyName)
                        <span style="opacity: 0.5;">•</span>
                        <span style="color: #fde047; font-weight: 700;">{{ $companyName }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div style="text-align: right; background: rgba(255,255,255,0.08); padding: 10px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12);">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #a5b4fc; font-weight: 700;">Tanggal Presensi</div>
            <div style="font-size: 15px; font-weight: 700; color: #ffffff; margin-top: 2px;">{{ $formattedDate }}</div>
        </div>
    </div>

    {{-- 4 Kartu Ringkasan Metrik Live Tracking & Presensi --}}
    <div class="metric-grid-4">
        {{-- Card 1: Status Presensi & Jam In/Out --}}
        <div class="metric-card">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Status Presensi</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; background: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusBorder }}; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $statusColor }};"></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 8px 10px;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">IN</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 1px; font-family: monospace;">
                            {{ $checkinTime ?? '--:--:--' }}
                        </div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 8px 10px;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">OUT</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 1px; font-family: monospace;">
                            {{ $checkoutTime ?? '--:--:--' }}
                        </div>
                    </div>
                </div>
            </div>

            @if($workDuration)
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                    <span style="color: #64748b;">Durasi Kerja:</span>
                    <span style="font-weight: 700; color: #4f46e5;">{{ $workDuration }}</span>
                </div>
            @endif
        </div>

        {{-- Card 2: Shift Roster & Lokasi Terdaftar --}}
        <div class="metric-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.04em;">Shift & Penempatan</div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                    {{ $shiftName }}
                    @if($shiftTime)
                        <span style="font-size: 11px; font-weight: 500; color: #64748b;">({{ $shiftTime }})</span>
                    @endif
                </div>
                <div style="font-size: 12px; font-weight: 600; color: #334155; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $scheduledLocation }}">
                    📍 {{ $scheduledLocation }}
                </div>
            </div>

            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b;">
                Jadwal: <span style="font-weight: 600; color: #1e293b;">{{ $schedule?->schedule_type ? ucfirst($schedule->schedule_type) : 'Workday' }}</span>
            </div>
        </div>

        {{-- Card 3: Total Titik GPS & Jarak --}}
        <div class="metric-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.04em;">Total Titik GPS</div>
                <div style="display: flex; align-items: baseline; gap: 8px;">
                    <span style="font-size: 24px; font-weight: 800; color: #0284c7; font-family: monospace;">
                        {{ count($trackingHistories) }}
                    </span>
                    <span style="font-size: 12px; font-weight: 600; color: #64748b;">Titik Terekam</span>
                </div>
            </div>

            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                <span style="color: #64748b;">Estimasi Jarak:</span>
                <span style="font-weight: 700; color: #0284c7;">{{ $formattedDistance }}</span>
            </div>
        </div>

        {{-- Card 4: Rentang Waktu Tracking --}}
        <div class="metric-card">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.04em;">Rentang Waktu Pelacakan</div>
                <div style="font-size: 12px; color: #334155; display: flex; flex-direction: column; gap: 4px;">
                    <div>🟢 Mulai: <strong style="font-family: monospace; color: #0f172a;">{{ $firstPointTime ?? '-' }} WIB</strong></div>
                    <div>🔴 Akhir: <strong style="font-family: monospace; color: #0f172a;">{{ $lastPointTime ?? '-' }} WIB</strong></div>
                </div>
            </div>

            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b;">
                Status: <span style="font-weight: 700; color: #059669;">✓ Data Tersinkron</span>
            </div>
        </div>
    </div>

    {{-- Side-by-Side Map & Activity Timeline Section --}}
    <div class="map-grid-container">
        {{-- Kolom Kiri: Peta Leaflet Interaktif --}}
        <div class="tracking-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">🗺️ Peta Jalur Pergerakan Karyawan</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Garis biru menunjukkan rute perpindahan yang dilalui karyawan selama jam kerja.</div>
                </div>
                <button 
                    type="button" 
                    id="btn-fit-bounds" 
                    style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.15s ease;"
                    onmouseover="this.style.background='#e2e8f0';"
                    onmouseout="this.style.background='#f1f5f9';"
                >
                    <i class="fa-solid fa-expand"></i> Reset Tampilan Peta
                </button>
            </div>

            {{-- Map container: relative so canvas overlay positions correctly --}}
            <div id="map-wrapper" style="position: relative; height: 560px; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc;">
                <div id="map" style="position: absolute; inset: 0; z-index: 1;"></div>
                <canvas id="route-canvas" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;"></canvas>
            </div>

            @if(empty($trackingHistories))
                <div style="margin-top: 14px; text-align: center; font-size: 13px; color: #64748b; background: #f8fafc; padding: 18px; border-radius: 10px; border: 1px dashed #cbd5e1;">
                    📍 Belum ada data koordinat GPS yang terekam pada tanggal ini.
                </div>
            @endif
        </div>

        {{-- Kolom Kanan: Timeline Log Presensi & Checkpoints --}}
        <div class="tracking-card" style="display: flex; flex-direction: column; max-height: 640px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                <div>
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a;">📍 Checkpoint & Log Presensi</div>
                    <div style="font-size: 11px; color: #64748b;">Klik item untuk zoom ke peta</div>
                </div>
                <span style="font-size: 11px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 3px 10px; border-radius: 12px;">
                    {{ count($activityLogs) }} Log
                </span>
            </div>

            <div style="overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px; flex: 1;">
                {{-- Log Presensi Khusus (Check-in, Visit, Checkout) --}}
                @forelse($activityLogs as $log)
                    @php
                        $logTypeLabel = match($log->log_type) {
                            'checkin'       => 'Check In',
                            'checkout'      => 'Check Out',
                            'visit_in'      => 'Visit In',
                            'visit_out'     => 'Visit Out',
                            'visit_report'  => 'Laporan Visit',
                            default         => str_replace('_', ' ', ucfirst($log->log_type)),
                        };

                        $logBg = match($log->log_type) {
                            'checkin'  => '#ecfdf5',
                            'checkout' => '#fffbeb',
                            'visit_in' => '#f0f9ff',
                            'visit_out'=> '#f5f3ff',
                            default    => '#faf5ff',
                        };

                        $logColor = match($log->log_type) {
                            'checkin'  => '#047857',
                            'checkout' => '#b45309',
                            'visit_in' => '#0369a1',
                            'visit_out'=> '#6d28d9',
                            default    => '#7e22ce',
                        };

                        $logBorder = match($log->log_type) {
                            'checkin'  => '#a7f3d0',
                            'checkout' => '#fde68a',
                            'visit_in' => '#bae6fd',
                            'visit_out'=> '#ddd6fe',
                            default    => '#e9d5ff',
                        };

                        $photoUrl = null;
                        if (!empty($log->photo_path)) {
                            try {
                                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($log->photo_path)) {
                                    $photoUrl = asset('storage/' . $log->photo_path);
                                } elseif (\Illuminate\Support\Facades\Storage::exists($log->photo_path)) {
                                    $photoUrl = \Illuminate\Support\Facades\Storage::url($log->photo_path);
                                }
                            } catch (\Throwable $e) {}
                        }
                    @endphp

                    <div 
                        class="activity-checkpoint-item"
                        data-lat="{{ $log->latitude }}"
                        data-lng="{{ $log->longitude }}"
                        title="Klik untuk memusatkan peta pada koordinat ini"
                    >
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span style="background: {{ $logBg }}; color: {{ $logColor }}; border: 1px solid {{ $logBorder }}; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                {{ $logTypeLabel }}
                            </span>
                            <span style="font-size: 11px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                {{ \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->format('H:i:s') }} WIB
                            </span>
                        </div>

                        @if($photoUrl)
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <img src="{{ $photoUrl }}" alt="Selfie" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; flex-shrink: 0;">
                                <div style="font-size: 10px; color: #64748b;">
                                    <div style="font-weight: 600; color: #1e293b;">Foto Selfie Presensi</div>
                                    <div style="color: #059669; font-weight: 700;">✓ Terverifikasi</div>
                                </div>
                            </div>
                        @endif

                        @if($log->latitude && $log->longitude)
                            <div style="font-size: 10px; font-family: monospace; color: #64748b;">
                                📍 {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 20px 0;">
                        Tidak ada checkpoint aktivitas presensi.
                    </div>
                @endforelse

                {{-- Daftar Titik GPS Tracking Singkat --}}
                @if(count($trackingHistories) > 0)
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #64748b;">
                        Titik Koordinat GPS ({{ count($trackingHistories) }})
                    </div>

                    @foreach(array_slice($trackingHistories, 0, 50) as $i => $point)
                        <div 
                            class="tracking-row-item"
                            data-lat="{{ $point['latitude'] }}"
                            data-lng="{{ $point['longitude'] }}"
                            data-index="{{ $i }}"
                            title="Klik untuk melihat titik ke-{{ $i + 1 }} di peta"
                        >
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="width: 20px; height: 20px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    {{ $i + 1 }}
                                </span>
                                <span style="font-family: monospace; color: #334155; font-size: 10px;">
                                    {{ number_format($point['latitude'], 5) }}, {{ number_format($point['longitude'], 5) }}
                                </span>
                            </div>
                            <span style="font-family: monospace; color: #64748b; font-weight: 600; font-size: 10px;">
                                {{ $point['created_at'] }}
                            </span>
                        </div>
                    @endforeach

                    @if(count($trackingHistories) > 50)
                        <div style="text-align: center; font-size: 11px; color: #64748b; padding: 4px;">
                            + {{ count($trackingHistories) - 50 }} titik lainnya (lihat tabel di bawah)
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Tabel Rincian Seluruh Koordinat Lokasi (Bottom Table) --}}
    @if(!empty($trackingHistories))
        <div class="tracking-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div>
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">📊 Tabel Rincian Seluruh Titik Koordinat GPS ({{ count($trackingHistories) }})</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Klik salah satu baris untuk memperbesar dan memusatkan lokasi pada peta di atas.</div>
                </div>
            </div>

            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; text-align: left; font-size: 11px; text-transform: uppercase;">
                            <th style="padding: 10px 14px; width: 60px;">#</th>
                            <th style="padding: 10px 14px;">Waktu (WIB)</th>
                            <th style="padding: 10px 14px;">Latitude</th>
                            <th style="padding: 10px 14px;">Longitude</th>
                            <th style="padding: 10px 14px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trackingHistories as $i => $point)
                            <tr 
                                class="tracking-table-row"
                                data-lat="{{ $point['latitude'] }}"
                                data-lng="{{ $point['longitude'] }}"
                                data-index="{{ $i }}"
                                style="border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s ease;"
                                onmouseover="this.style.background='#f8fafc';"
                                onmouseout="this.style.background='transparent';"
                            >
                                <td style="padding: 10px 14px; color: #94a3b8; font-weight: 600;">{{ $i + 1 }}</td>
                                <td style="padding: 10px 14px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                    🕒 {{ $point['created_at'] }} WIB
                                </td>
                                <td style="padding: 10px 14px; font-family: monospace; color: #334155;">
                                    {{ number_format($point['latitude'], 6) }}
                                </td>
                                <td style="padding: 10px 14px; font-family: monospace; color: #334155;">
                                    {{ number_format($point['longitude'], 6) }}
                                </td>
                                <td style="padding: 10px 14px; text-align: right;">
                                    <a 
                                        href="https://maps.google.com/maps?q={{ $point['latitude'] }},{{ $point['longitude'] }}" 
                                        target="_blank" 
                                        style="color: #2563eb; font-weight: 700; text-decoration: none; font-size: 11px;"
                                        onclick="event.stopPropagation();"
                                    >
                                        Google Maps ↗
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trackingData = @json($trackingHistories);
    let map;
    let markers = [];
    let allLatLngBounds = null;

    const canvas = document.getElementById('route-canvas');
    const ctx    = canvas ? canvas.getContext('2d') : null;

    function drawRoute() {
        if (!map || !ctx || trackingData.length < 2) return;

        const rect = canvas.getBoundingClientRect();
        canvas.width  = rect.width  || canvas.offsetWidth;
        canvas.height = rect.height || canvas.offsetHeight;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw line
        ctx.beginPath();
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth   = 4;
        ctx.lineJoin    = 'round';
        ctx.lineCap     = 'round';
        ctx.globalAlpha = 0.9;

        trackingData.forEach(function(point, i) {
            const pt = map.latLngToContainerPoint([
                parseFloat(point.latitude),
                parseFloat(point.longitude)
            ]);
            if (i === 0) ctx.moveTo(pt.x, pt.y);
            else         ctx.lineTo(pt.x, pt.y);
        });
        ctx.stroke();

        // Intermediate dots
        trackingData.forEach(function(point, i) {
            if (i === 0 || i === trackingData.length - 1) return;

            const pt = map.latLngToContainerPoint([
                parseFloat(point.latitude),
                parseFloat(point.longitude)
            ]);

            ctx.beginPath();
            ctx.arc(pt.x, pt.y, 4, 0, 2 * Math.PI);
            ctx.fillStyle   = '#38bdf8';
            ctx.globalAlpha = 0.9;
            ctx.fill();
            ctx.strokeStyle = '#0284c7';
            ctx.lineWidth   = 1.5;
            ctx.globalAlpha = 1;
            ctx.stroke();
        });

        ctx.globalAlpha = 1;
    }

    if (trackingData.length > 0) {
        map = L.map('map').setView(
            [parseFloat(trackingData[0].latitude), parseFloat(trackingData[0].longitude)],
            15
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const first = trackingData[0];
        const last  = trackingData[trackingData.length - 1];

        // Custom green start icon
        const startIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#10b981; width:28px; height:28px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 3px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:12px;'>A</div>",
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        // Custom red end icon
        const endIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#ef4444; width:28px; height:28px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 3px 8px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:12px;'>B</div>",
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        const startMarker = L.marker([parseFloat(first.latitude), parseFloat(first.longitude)], { icon: startIcon })
            .addTo(map)
            .bindPopup('<div style="font-family:sans-serif;"><strong style="color:#059669;">Titik Awal (Start)</strong><br>Waktu: ' + first.created_at + ' WIB</div>');

        const endMarker = L.marker([parseFloat(last.latitude), parseFloat(last.longitude)], { icon: endIcon })
            .addTo(map)
            .bindPopup('<div style="font-family:sans-serif;"><strong style="color:#dc2626;">Titik Terakhir (End)</strong><br>Waktu: ' + last.created_at + ' WIB</div>');

        markers.push(startMarker);
        for (let j = 1; j < trackingData.length - 1; j++) markers.push(null);
        markers.push(endMarker);

        const latlngs = trackingData.map(function(p) {
            return [parseFloat(p.latitude), parseFloat(p.longitude)];
        });
        allLatLngBounds = L.latLngBounds(latlngs);
        map.fitBounds(allLatLngBounds, { padding: [50, 50] });

        map.on('move zoom viewreset zoomstart zoomend moveend', drawRoute);
        setTimeout(drawRoute, 350);

    } else {
        map = L.map('map').setView([-7.2575, 112.7521], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
    }

    // Fit bounds button
    const btnFit = document.getElementById('btn-fit-bounds');
    if (btnFit && map && allLatLngBounds) {
        btnFit.addEventListener('click', function () {
            map.fitBounds(allLatLngBounds, { padding: [50, 50] });
            setTimeout(drawRoute, 300);
        });
    }

    // Click on table row or checkpoint item -> jump to location
    document.querySelectorAll('.tracking-row-item, .activity-checkpoint-item, .tracking-table-row').forEach(function(row) {
        row.addEventListener('click', function () {
            const lat = parseFloat(this.dataset.lat);
            const lng = parseFloat(this.dataset.lng);
            if (lat && lng && map) {
                map.setView([lat, lng], 18);
                const idx = parseInt(this.dataset.index);
                if (!isNaN(idx) && markers[idx]) {
                    markers[idx].openPopup();
                }
                setTimeout(drawRoute, 300);

                // Smooth scroll up to map if clicked from bottom table
                if (this.classList.contains('tracking-table-row')) {
                    document.getElementById('map-wrapper').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });

    window.addEventListener('resize', function() {
        setTimeout(drawRoute, 150);
    });
});
</script>
@endpush
