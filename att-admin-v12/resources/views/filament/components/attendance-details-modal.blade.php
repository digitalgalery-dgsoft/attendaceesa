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
    
    $companyName = $employee?->principal?->name 
        ?? $employee?->company?->name
        ?? $schedule?->workLocation?->company?->name;

    $initials = $employee ? strtoupper(substr($employee->full_name, 0, 2)) : 'PR';
@endphp

<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5;">
    
    {{-- Header Banner Karyawan & Tanggal --}}
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); color: #ffffff; border-radius: 14px; padding: 18px 22px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: #ffffff; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4); flex-shrink: 0;">
                {{ $initials }}
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">
                        {{ $employee?->full_name ?? 'Karyawan' }}
                    </span>
                    @if($employee?->employee_no)
                        <span style="font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.15); color: #e0e7ff; padding: 2px 8px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">
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

        <div style="text-align: right; background: rgba(255,255,255,0.08); padding: 8px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12);">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #a5b4fc; font-weight: 700;">Tanggal Presensi</div>
            <div style="font-size: 14px; font-weight: 700; color: #ffffff; margin-top: 2px;">{{ $formattedDate }}</div>
        </div>
    </div>

    {{-- Penyesuaian Manual / Import Alert --}}
    @if($attendance && $attendance->is_manual_correction)
        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 12px 16px; margin-top: 14px; display: flex; align-items: flex-start; gap: 10px;">
            <span style="font-size: 16px;">⚡</span>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #7e22ce; text-transform: uppercase; letter-spacing: 0.04em;">Data Hasil Penyesuaian / Import Excel</div>
                <div style="font-size: 12px; color: #581c87; margin-top: 2px;">
                    <strong>Catatan:</strong> {{ $attendance->correction_note ?: 'Disinkronkan melalui Import Excel oleh Admin' }}
                </div>
            </div>
        </div>
    @endif

    {{-- 3 Kartu Ringkasan Status, Shift & GPS Tracking --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-top: 16px;">
        
        {{-- Card 1: Status Kehadiran --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Status Presensi</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; background: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusBorder }}; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $statusColor }};"></span>
                        {{ $statusLabel }}
                        @if($status === 'late' && $attendance?->late_minutes > 0)
                            (+{{ $attendance->late_minutes }}m)
                        @endif
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 8px 10px;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">CHECK-IN</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; font-family: monospace;">
                            {{ $checkinTime ?? '--:--:--' }}
                        </div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 8px 10px;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">CHECK-OUT</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; font-family: monospace;">
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

        {{-- Card 2: Shift Roster & Lokasi --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Jadwal Shift Roster</span>
                    <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700;">
                        {{ $schedule?->schedule_type ? ucfirst($schedule->schedule_type) : 'Workday' }}
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">SHIFT KERJA</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                            {{ $shiftName }}
                            @if($shiftTime)
                                <span style="font-size: 11px; font-weight: 500; color: #64748b;">({{ $shiftTime }})</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">LOKASI PENEMPATAN</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $scheduledLocation }}">
                            📍 {{ $scheduledLocation }}
                        </div>
                    </div>
                </div>
            </div>

            @if($schedule?->planned_start_at)
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                    <span style="color: #64748b;">Target Masuk:</span>
                    <span style="font-family: monospace; font-weight: 600; color: #334155;">{{ \Carbon\Carbon::parse($schedule->planned_start_at)->format('H:i') }} WIB</span>
                </div>
            @endif
        </div>

        {{-- Card 3: Live Tracking GPS --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Live Tracking (GPS)</span>
                    <span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700;">
                        {{ $trackingCount ?? 0 }} Titik GPS
                    </span>
                </div>

                <div style="font-size: 11px; color: #64748b; line-height: 1.4;">
                    Riwayat perpindahan posisi karyawan selama jam kerja otomatis direkam oleh sistem.
                </div>
            </div>

            <div style="margin-top: 12px;">
                @if($attendance)
                    <a
                        href="{{ \App\Filament\Resources\Attendances\AttendanceResource::getUrl('view-route', ['record' => $attendance->id]) }}"
                        target="_blank"
                        style="display: block; text-align: center; background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #ffffff; font-size: 11px; font-weight: 700; padding: 8px 12px; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 6px rgba(37,99,235,0.25);"
                    >
                        🗺️ Lihat Rute Live Tracking (Peta)
                    </a>
                @else
                    <div style="text-align: center; background: #f1f5f9; color: #94a3b8; font-size: 11px; font-weight: 600; padding: 8px 12px; border-radius: 8px;">
                        Tracking Tidak Tersedia
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Bagian Riwayat Log Aktivitas & Bukti Lokasi (Activity Logs) --}}
    <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a;">📋 Riwayat Log Presensi & Lokasi (Activity Logs)</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 1px;">Daftar aktivitas check-in, checkout, dan kunjungan pada tanggal ini.</div>
            </div>
            <span style="font-size: 11px; font-weight: 700; background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 12px; border: 1px solid #e2e8f0;">
                Total: {{ count($logs ?? []) }} Log
            </span>
        </div>

        @if (empty($logs) || count($logs) === 0)
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 28px; text-align: center; color: #64748b;">
                <div style="font-size: 28px; margin-bottom: 6px;">📅</div>
                <div style="font-size: 13px; font-weight: 700; color: #334155;">Belum Ada Log Aktivitas</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Tidak ada log check-in atau aktivitas yang terekam pada hari ini.</div>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($logs as $log)
                    @php
                        $logTypeLabel = match($log->log_type) {
                            'checkin'       => 'Check In',
                            'checkout'      => 'Check Out',
                            'visit_in'      => 'Visit In (Kunjungan Masuk)',
                            'visit_out'     => 'Visit Out (Kunjungan Selesai)',
                            'visit_report'  => 'Laporan Kunjungan',
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

                        $itItem = $log->itinerary_item_id ? \App\Models\ItineraryItem::with('workLocation')->find($log->itinerary_item_id) : null;
                        $locationName = $log->address_text 
                            ?: ($itItem?->workLocation?->name 
                            ?: ($log->latitude && $log->longitude ? number_format($log->latitude, 6) . ', ' . number_format($log->longitude, 6) : 'Lokasi tidak terekam'));
                        
                        $photoUrl = $log->photo_path ? \Illuminate\Support\Facades\Storage::url($log->photo_path) : null;
                    @endphp

                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        {{-- Bar Atas: Tipe Log, Jam & Status Geofence --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding-bottom: 10px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="background: {{ $logBg }}; color: {{ $logColor }}; border: 1px solid {{ $logBorder }}; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                    {{ $logTypeLabel }}
                                </span>
                                <span style="font-size: 13px; font-weight: 700; color: #0f172a; font-family: monospace;">
                                    🕒 {{ \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->format('H:i:s') }} WIB
                                </span>
                            </div>

                            @if(!is_null($log->is_inside_geofence))
                                <div>
                                    @if($log->is_inside_geofence)
                                        <span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">
                                            ✓ Dalam Radius Kantor
                                            @if($log->distance_from_location_meter)
                                                ({{ round($log->distance_from_location_meter) }}m)
                                            @endif
                                        </span>
                                    @else
                                        <span style="background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">
                                            ⚠️ Di Luar Radius
                                            @if($log->distance_from_location_meter)
                                                ({{ round($log->distance_from_location_meter) }}m)
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Layout 2 Kolom (Kiri: Alamat, Foto Thumbnail Kecil, Catatan | Kanan: Peta Embed) --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; align-items: start;">
                            
                            {{-- Kolom Kiri: Alamat, Koordinat, Catatan & Foto Thumbnail --}}
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                
                                {{-- Box Alamat & Koordinat --}}
                                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                                    <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lokasi Presensi</div>
                                    <div style="font-size: 12px; font-weight: 600; color: #1e293b; margin-top: 2px;">
                                        📍 {{ $locationName }}
                                    </div>

                                    @if($log->latitude && $log->longitude)
                                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                            <span style="font-family: monospace; color: #64748b;">
                                                {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                                                @if($log->accuracy_meter)
                                                    <span style="color: #94a3b8;">(±{{ round($log->accuracy_meter) }}m)</span>
                                                @endif
                                            </span>
                                            <a 
                                                href="https://maps.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}" 
                                                target="_blank" 
                                                style="color: #2563eb; font-weight: 600; text-decoration: none;"
                                            >
                                                Buka Maps ↗
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Catatan Jika Ada --}}
                                @if($log->note)
                                    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 8px 10px; font-size: 11px; color: #92400e;">
                                        <strong>💬 Catatan:</strong> "{{ $log->note }}"
                                    </div>
                                @endif

                                {{-- Foto Selfie Thumbnail Kecil (70x70 px) --}}
                                @if($photoUrl)
                                    <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 8px 12px;">
                                        <a href="{{ $photoUrl }}" target="_blank" title="Klik untuk memperbesar foto" style="display: block; flex-shrink: 0; position: relative;">
                                            <img 
                                                src="{{ $photoUrl }}" 
                                                alt="Foto Presensi" 
                                                style="width: 65px; height: 65px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1; display: block; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                                            >
                                        </a>
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; color: #0f172a;">Foto Selfie Presensi</div>
                                            <div style="font-size: 10px; color: #64748b; margin-top: 1px;">Kamera aktif saat verifikasi lokasi</div>
                                            <a href="{{ $photoUrl }}" target="_blank" style="display: inline-block; margin-top: 4px; font-size: 11px; font-weight: 600; color: #2563eb; text-decoration: none;">
                                                🔍 Lihat Foto Penuh ↗
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Kolom Kanan: Peta Google Maps Embed --}}
                            <div>
                                @if ($log->latitude && $log->longitude)
                                    <div style="overflow: hidden; border-radius: 10px; border: 1px solid #e2e8f0; height: 165px; background: #f1f5f9;">
                                        <iframe 
                                            width="100%" 
                                            height="100%" 
                                            style="border: 0; display: block;" 
                                            loading="lazy" 
                                            allowfullscreen 
                                            src="https://maps.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}&z=15&output=embed">
                                        </iframe>
                                    </div>
                                @else
                                    <div style="height: 165px; border-radius: 10px; border: 1px dashed #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #94a3b8;">
                                        Peta tidak tersedia
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
