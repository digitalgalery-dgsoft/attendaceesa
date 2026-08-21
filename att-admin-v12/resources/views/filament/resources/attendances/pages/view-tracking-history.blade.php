@php
    $formattedDate = \Carbon\Carbon::parse($record->attendance_date)->locale('id')->translatedFormat('l, d F Y');
    
    // Status Presensi
    $status = $record->status;
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
    $checkinTime = $record->checkin_at 
        ? \Carbon\Carbon::parse($record->checkin_at)->timezone('Asia/Jakarta')->format('H:i:s') 
        : null;
    $checkoutTime = $record->checkout_at 
        ? \Carbon\Carbon::parse($record->checkout_at)->timezone('Asia/Jakarta')->format('H:i:s') 
        : null;

    // Hitung Durasi Kerja
    $workDuration = null;
    if ($record->checkin_at) {
        $start = \Carbon\Carbon::parse($record->checkin_at);
        $end = $record->checkout_at ? \Carbon\Carbon::parse($record->checkout_at) : null;
        if ($end) {
            $diffMinutes = $start->diffInMinutes($end);
            $hours = floor($diffMinutes / 60);
            $minutes = $diffMinutes % 60;
            $workDuration = ($hours > 0 ? "{$hours} Jam " : "") . "{$minutes} Menit";
        } elseif (\Carbon\Carbon::parse($record->attendance_date)->isToday()) {
            $workDuration = 'Sedang Bekerja';
        }
    }

    // Shift & Lokasi Terjadwal
    $shiftName = $schedule?->shift?->name ?? $record->employeeSchedule?->shift?->name ?? 'OFFICE';
    $shiftTime = null;
    if ($schedule?->shift?->start_time && $schedule?->shift?->end_time) {
        $shiftTime = substr($schedule->shift->start_time, 0, 5) . ' - ' . substr($schedule->shift->end_time, 0, 5);
    } elseif ($record->employeeSchedule?->shift?->start_time) {
        $shiftTime = substr($record->employeeSchedule->shift->start_time, 0, 5) . ' - ' . substr($record->employeeSchedule->shift->end_time, 0, 5);
    }

    $scheduledLocation = $schedule?->workLocation?->name 
        ?? $record->employeeSchedule?->workLocation?->name 
        ?? ($employee?->branch?->name ?? 'Belum Ditentukan');
    
    $companyName = $schedule?->workLocation?->company?->name 
        ?? $employee?->principal?->name 
        ?? $employee?->company?->name;

    $initials = $employee ? strtoupper(substr($employee->full_name, 0, 2)) : 'PR';

    // Format Total Distance
    $formattedDistance = $totalDistanceMeter >= 1000 
        ? number_format($totalDistanceMeter / 1000, 2) . ' km' 
        : round($totalDistanceMeter) . ' meter';

    $firstPointTime = !empty($trackingHistories) ? $trackingHistories[0]['created_at'] : null;
    $lastPointTime  = !empty($trackingHistories) ? $trackingHistories[count($trackingHistories) - 1]['created_at'] : null;
@endphp

<x-filament-panels::page>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; display: flex; flex-direction: column; gap: 18px;">
        
        {{-- Header Banner Karyawan & Tanggal (Matching Attendance Details Modal) --}}
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); color: #ffffff; border-radius: 16px; padding: 20px 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; color: #ffffff; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); flex-shrink: 0;">
                    {{ $initials }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">
                            {{ $employee?->full_name ?? $employeeName }}
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
                            <span style="color: #fde047; font-weight: 600;">{{ $companyName }}</span>
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            
            {{-- Card 1: Status Presensi & Jam --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Presensi</span>
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
                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                        <span style="color: #64748b;">Durasi:</span>
                        <span style="font-weight: 700; color: #4f46e5;">{{ $workDuration }}</span>
                    </div>
                @endif
            </div>

            {{-- Card 2: Shift Roster & Lokasi Terdaftar --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Shift & Penempatan</div>
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

                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b;">
                    Jadwal: <span style="font-weight: 600; color: #1e293b;">{{ $schedule?->schedule_type ? ucfirst($schedule->schedule_type) : 'Workday' }}</span>
                </div>
            </div>

            {{-- Card 3: Titik GPS & Jarak --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Total Titik GPS</div>
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-size: 22px; font-weight: 800; color: #0284c7; font-family: monospace;">
                            {{ count($trackingHistories) }}
                        </span>
                        <span style="font-size: 12px; font-weight: 600; color: #64748b;">Titik Terkam</span>
                    </div>
                </div>

                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                    <span style="color: #64748b;">Estimasi Jarak Rute:</span>
                    <span style="font-weight: 700; color: #0284c7;">{{ $formattedDistance }}</span>
                </div>
            </div>

            {{-- Card 4: Rentang Waktu Tracking --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Rentang Waktu Pelacakan</div>
                    <div style="font-size: 12px; color: #334155; display: flex; flex-direction: column; gap: 3px;">
                        <div>🟢 Mulai: <strong style="font-family: monospace;">{{ $firstPointTime ?? '-' }} WIB</strong></div>
                        <div>🔴 Akhir: <strong style="font-family: monospace;">{{ $lastPointTime ?? '-' }} WIB</strong></div>
                    </div>
                </div>

                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b;">
                    Status: <span style="font-weight: 600; color: #059669;">Tersinkronisasi Real-time</span>
                </div>
            </div>

        </div>

        {{-- Side-by-Side Map & Activity Timeline Section --}}
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start;">
            
            {{-- Kolom Kiri: Peta Leaflet Interaktif --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">🗺️ Peta Jalur Pergerakan Karyawan</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 1px;">Garis biru menunjukkan rute perpindahan yang dilalui karyawan.</div>
                    </div>
                    <button 
                        type="button" 
                        id="btn-fit-bounds" 
                        style="background: #f1f5f9; hover:background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 600; cursor: pointer;"
                    >
                        🔍 Reset Tampilan Peta
                    </button>
                </div>

                {{-- Map container: relative so canvas overlay positions correctly --}}
                <div id="map-wrapper" style="position: relative; height: 520px; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc;">
                    <div id="map" style="position: absolute; inset: 0; z-index: 1;"></div>
                    <canvas id="route-canvas" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;"></canvas>
                </div>

                @if(empty($trackingHistories))
                    <div style="margin-top: 12px; text-align: center; font-size: 12px; color: #64748b; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        📍 Belum ada data koordinat GPS yang terekam pada tanggal ini.
                    </div>
                @endif
            </div>

            {{-- Kolom Kanan: Timeline Log Presensi & Checkpoints --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; max-height: 600px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">📍 Checkpoint & Log Aktivitas</div>
                    <span style="font-size: 10px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 10px;">
                        {{ count($activityLogs) }} Log Presensi
                    </span>
                </div>

                <div style="overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
                    
                    {{-- Log Presensi Khusus (Check-in, Visit, Checkout) --}}
                    @foreach($activityLogs as $log)
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

                            $photoUrl = $log->photo_path ? \Illuminate\Support\Facades\Storage::url($log->photo_path) : null;
                        @endphp

                        <div 
                            class="activity-log-item"
                            data-lat="{{ $log->latitude }}"
                            data-lng="{{ $log->longitude }}"
                            style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';"
                            onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';"
                        >
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="background: {{ $logBg }}; color: {{ $logColor }}; border: 1px solid {{ $logBorder }}; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                    {{ $logTypeLabel }}
                                </span>
                                <span style="font-size: 11px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                    {{ \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->format('H:i:s') }}
                                </span>
                            </div>

                            @if($photoUrl)
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <img src="{{ $photoUrl }}" alt="Selfie" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div style="font-size: 10px; color: #64748b;">
                                        <div>Foto Selfie Presensi</div>
                                        <div style="color: #059669; font-weight: 600;">✓ Terverifikasi</div>
                                    </div>
                                </div>
                            @endif

                            @if($log->latitude && $log->longitude)
                                <div style="font-size: 10px; font-family: monospace; color: #64748b;">
                                    📍 {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Daftar Titik GPS Tracking Singkat --}}
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #64748b;">
                        Titik Koordinat GPS ({{ count($trackingHistories) }})
                    </div>

                    @foreach($trackingHistories as $i => $point)
                        <div 
                            class="tracking-row"
                            data-lat="{{ $point['latitude'] }}"
                            data-lng="{{ $point['longitude'] }}"
                            data-index="{{ $i }}"
                            style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px 10px; display: flex; align-items: center; justify-content: space-between; font-size: 11px; cursor: pointer; transition: all 0.15s;"
                            onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe';"
                            onmouseout="this.style.background='#ffffff'; this.style.borderColor='#f1f5f9';"
                        >
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="width: 18px; height: 18px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center;">
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

                </div>
            </div>

        </div>

        {{-- Tabel Rincian Seluruh Koordinat Lokasi (Bottom Table) --}}
        @if(!empty($trackingHistories))
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">📊 Tabel Rincian Seluruh Titik Koordinat GPS</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 1px;">Klik salah satu baris untuk memperbesar lokasi pada peta.</div>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; text-align: left; font-size: 11px; text-transform: uppercase;">
                                <th style="padding: 10px 14px; width: 50px;">#</th>
                                <th style="padding: 10px 14px;">Waktu (WIB)</th>
                                <th style="padding: 10px 14px;">Latitude</th>
                                <th style="padding: 10px 14px;">Longitude</th>
                                <th style="padding: 10px 14px; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trackingHistories as $i => $point)
                                <tr 
                                    class="tracking-row"
                                    data-lat="{{ $point['latitude'] }}"
                                    data-lng="{{ $point['longitude'] }}"
                                    data-index="{{ $i }}"
                                    style="border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s;"
                                    onmouseover="this.style.background='#f8fafc';"
                                    onmouseout="this.style.background='transparent';"
                                >
                                    <td style="padding: 10px 14px; color: #94a3b8; font-weight: 600;">{{ $i + 1 }}</td>
                                    <td style="padding: 10px 14px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                        🕒 {{ $point['created_at'] }}
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
                                            style="color: #2563eb; font-weight: 600; text-decoration: none; font-size: 11px;"
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

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const trackingData = @json($trackingHistories);
        let map;
        let markers = [];
        let allLatLngBounds = null;

        const canvas = document.getElementById('route-canvas');
        const ctx    = canvas.getContext('2d');

        function drawRoute() {
            if (!map || trackingData.length < 2) return;

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

            // Custom blue/red end icon
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
        document.querySelectorAll('.tracking-row, .activity-log-item').forEach(function(row) {
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
                }
            });
        });

        window.addEventListener('resize', function() {
            setTimeout(drawRoute, 150);
        });
    });
    </script>
</x-filament-panels::page>
