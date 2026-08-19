<x-filament-panels::page>
    <style>
        .meeting-report-grid-4col {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }
        .meeting-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 1024px) {
            .meeting-report-grid-4col {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .meeting-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 640px) {
            .meeting-report-grid-4col {
                grid-template-columns: 1fr !important;
            }
            .meeting-info-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .meeting-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .meeting-table th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .meeting-table td {
            padding: 14px 16px;
            font-size: 13px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }
        .meeting-table tr:last-child td {
            border-bottom: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
        }
    </style>

    @php
        $meeting = $this->record;
        $participants = $meeting->participants ?? collect();
        $attendances = ($meeting->attendances ?? collect())->keyBy('employee_id');

        $completedCount = 0;
        $inMeetingCount = 0;
        $notAttendedCount = 0;

        foreach ($participants as $p) {
            $att = $attendances->get($p->employee_id);
            if ($att && $att->status === 'completed') {
                $completedCount++;
            } elseif ($att && $att->status === 'in_meeting') {
                $inMeetingCount++;
            } else {
                $notAttendedCount++;
            }
        }

        $totalParticipants = $participants->count();
        $attendanceRate = $totalParticipants > 0 ? round(($completedCount / $totalParticipants) * 100) : 0;
    @endphp

    {{-- 1. Meeting Overview Header Card --}}
    <x-filament::section>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Header Row --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 14px;" class="dark:!border-white/10">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: {{ $meeting->meeting_type === 'online' ? 'rgba(59, 130, 246, 0.12)' : 'rgba(16, 185, 129, 0.12)' }}; display: flex; align-items: center; justify-content: center; color: {{ $meeting->meeting_type === 'online' ? '#2563eb' : '#059669' }};">
                        <x-filament::icon icon="{{ $meeting->meeting_type === 'online' ? 'heroicon-o-video-camera' : 'heroicon-o-building-office-2' }}" style="width: 26px; height: 26px;" />
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 20px; font-weight: 800; color: #111827;" class="dark:!text-white">{{ $meeting->title }}</span>
                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: {{ $meeting->meeting_type === 'online' ? '#dbeafe' : '#d1fae5' }}; color: {{ $meeting->meeting_type === 'online' ? '#1e40af' : '#065f46' }};">
                                {{ strtoupper($meeting->meeting_type) }}
                            </span>
                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: {{ $meeting->status === 'completed' ? '#dcfce7' : ($meeting->status === 'in_progress' ? '#fef3c7' : '#f3f4f6') }}; color: {{ $meeting->status === 'completed' ? '#15803d' : ($meeting->status === 'in_progress' ? '#b45309' : '#374151') }};">
                                {{ strtoupper($meeting->status) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 2px;" class="dark:!text-gray-400">
                            ID Meeting: #{{ $meeting->id }} · Dibuat oleh: <strong>{{ $meeting->creator?->name ?? 'Admin' }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Action Quick Buttons --}}
                <div style="display: flex; align-items: center; gap: 8px;" class="no-print">
                    @if ($meeting->meeting_type === 'online' && $meeting->meeting_link)
                        <x-filament::button tag="a" href="{{ $meeting->meeting_link }}" target="_blank" color="info" icon="heroicon-o-arrow-top-right-on-square" size="sm">
                            Buka Link Meeting
                        </x-filament::button>
                    @endif
                    <x-filament::button onclick="window.print()" color="gray" icon="heroicon-o-printer" size="sm">
                        Cetak Laporan
                    </x-filament::button>
                </div>
            </div>

            {{-- Info Details Grid --}}
            <div class="meeting-info-grid">
                {{-- Tanggal --}}
                <div style="background: rgba(0,0,0,0.02); border-radius: 10px; padding: 12px 14px; border: 1px solid rgba(0,0,0,0.05);" class="dark:!bg-white/5 dark:!border-white/10">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 2px;">Tanggal Pelaksanaan</div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827;" class="dark:!text-white">
                        {{ $meeting->meeting_date ? \Carbon\Carbon::parse($meeting->meeting_date)->locale('id')->isoFormat('dddd, D MMMM Y') : '-' }}
                    </div>
                </div>

                {{-- Waktu --}}
                <div style="background: rgba(0,0,0,0.02); border-radius: 10px; padding: 12px 14px; border: 1px solid rgba(0,0,0,0.05);" class="dark:!bg-white/5 dark:!border-white/10">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 2px;">Waktu Meeting</div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827;" class="dark:!text-white">
                        {{ substr($meeting->start_time, 0, 5) }} {{ $meeting->end_time ? '- ' . substr($meeting->end_time, 0, 5) . ' WIB' : 'WIB' }}
                    </div>
                </div>

                {{-- Lokasi / Link --}}
                <div style="background: rgba(0,0,0,0.02); border-radius: 10px; padding: 12px 14px; border: 1px solid rgba(0,0,0,0.05);" class="dark:!bg-white/5 dark:!border-white/10">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 2px;">
                        {{ $meeting->meeting_type === 'online' ? 'Link Meeting' : 'Lokasi & Radius Lock' }}
                    </div>
                    <div style="font-size: 13px; font-weight: 600; color: #111827; word-break: break-all;" class="dark:!text-white">
                        @if ($meeting->meeting_type === 'online')
                            <a href="{{ $meeting->meeting_link }}" target="_blank" style="color: #2563eb; text-decoration: underline;">
                                {{ $meeting->meeting_link ?: '-' }}
                            </a>
                        @else
                            {{ $meeting->location_name ?: '-' }}
                            @if ($meeting->radius_meter)
                                <span style="font-size: 11px; color: #059669; font-weight: 700; margin-left: 4px;">(Radius {{ $meeting->radius_meter }}m)</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Agenda / Pembahasan --}}
            @if ($meeting->notes)
                <div style="background: rgba(59, 130, 246, 0.04); border-left: 4px solid #3b82f6; border-radius: 6px; padding: 10px 14px; font-size: 12.5px;">
                    <strong style="color: #1e40af;" class="dark:!text-blue-400">Agenda / Topik Pembahasan:</strong>
                    <div style="color: #374151; margin-top: 4px; line-height: 1.5;" class="dark:!text-gray-300">
                        {{ $meeting->notes }}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- 2. KPI Stat Cards --}}
    <div class="meeting-report-grid-4col">
        {{-- Total Peserta --}}
        <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #2563eb; display: block; margin-bottom: 4px;">Total Peserta</span>
                    <span style="font-size: 26px; font-weight: 800; color: #1d4ed8; line-height: 1;">{{ $totalParticipants }}</span>
                    <span style="font-size: 11px; color: #6b7280; display: block; margin-top: 4px;">Orang terdaftar</span>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); display: flex; align-items: center; justify-content: center; color: #2563eb;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 22px; height: 22px;" />
                </div>
            </div>
        </div>

        {{-- Hadir / Selesai --}}
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02)); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #059669; display: block; margin-bottom: 4px;">Hadir & Selesai</span>
                    <span style="font-size: 26px; font-weight: 800; color: #047857; line-height: 1;">{{ $completedCount }}</span>
                    <span style="font-size: 11px; color: #059669; font-weight: 700; display: block; margin-top: 4px;">{{ $attendanceRate }}% Kehadiran</span>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; color: #059669;">
                    <x-filament::icon icon="heroicon-o-check-badge" style="width: 22px; height: 22px;" />
                </div>
            </div>
        </div>

        {{-- Sedang Rapat --}}
        <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02)); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #d97706; display: block; margin-bottom: 4px;">Sedang Rapat</span>
                    <span style="font-size: 26px; font-weight: 800; color: #b45309; line-height: 1;">{{ $inMeetingCount }}</span>
                    <span style="font-size: 11px; color: #b45309; display: block; margin-top: 4px;">In Meeting</span>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center; color: #d97706;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 22px; height: 22px;" />
                </div>
            </div>
        </div>

        {{-- Belum Hadir --}}
        <div style="background: linear-gradient(135deg, rgba(148, 163, 184, 0.08), rgba(148, 163, 184, 0.02)); border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 12px; padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; display: block; margin-bottom: 4px;">Belum Hadir</span>
                    <span style="font-size: 26px; font-weight: 800; color: #475569; line-height: 1;" class="dark:!text-gray-300">{{ $notAttendedCount }}</span>
                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Tanpa Presensi</span>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(148, 163, 184, 0.15); display: flex; align-items: center; justify-content: center; color: #64748b;">
                    <x-filament::icon icon="heroicon-o-user-minus" style="width: 22px; height: 22px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Detail Kehadiran & Laporan Notulensi Peserta --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px;">
                <x-filament::icon icon="heroicon-o-clipboard-document-list" style="width: 20px; height: 20px; color: #2563eb;" />
                <span>Daftar Presensi & Laporan Peserta Meeting</span>
            </div>
        </x-slot>

        <div style="overflow-x: auto; margin-top: 8px;">
            <table class="meeting-table">
                <thead style="background: rgba(0,0,0,0.02);" class="dark:!bg-white/5">
                    <tr>
                        <th style="width: 40px; text-align: center; color: #6b7280;">No</th>
                        <th style="color: #6b7280;">Nama Peserta</th>
                        <th style="color: #6b7280;">Status</th>
                        <th style="color: #6b7280;">Meet-In</th>
                        <th style="color: #6b7280;">Meet-Out</th>
                        <th style="color: #6b7280;">Durasi</th>
                        <th style="color: #6b7280; min-width: 240px;">Catatan / Notulensi Rapat</th>
                        <th style="text-align: center; color: #6b7280; width: 120px;">Foto Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participants as $index => $p)
                        @php
                            $emp = $p->employee;
                            $att = $attendances->get($p->employee_id);
                            $photo = $att?->meet_out_photo ?? $att?->meet_in_photo;
                            $durationFormatted = '-';
                            if ($att && $att->duration_seconds) {
                                $hours = floor($att->duration_seconds / 3600);
                                $mins = floor(($att->duration_seconds % 3600) / 60);
                                $secs = $att->duration_seconds % 60;
                                $durationFormatted = ($hours > 0 ? sprintf('%02d:%02d:%02d', $hours, $mins, $secs) : sprintf('%02d:%02d', $mins, $secs));
                            } elseif ($att && $att->status === 'in_meeting') {
                                $durationFormatted = 'Sedang Berlangsung';
                            }
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #9ca3af;">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #111827;" class="dark:!text-white">
                                    {{ $emp?->full_name ?? ($emp?->name ?? 'Karyawan') }}
                                </div>
                                <div style="font-size: 11px; color: #6b7280; margin-top: 1px;" class="dark:!text-gray-400">
                                    NIK: {{ $emp?->employee_no ?? '-' }} · {{ is_object($emp?->position) ? $emp->position->name : ($emp?->position ?? '-') }}
                                </div>
                                @if ($emp?->principal)
                                    <div style="font-size: 11px; color: #2563eb; font-weight: 600; margin-top: 2px;">
                                        Principal: {{ $emp->principal->name }}
                                    </div>
                                @elseif ($emp?->principal_name)
                                    <div style="font-size: 11px; color: #2563eb; font-weight: 600; margin-top: 2px;">
                                        Principal: {{ $emp->principal_name }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($att && $att->status === 'completed')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #15803d;">
                                        <x-filament::icon icon="heroicon-s-check-circle" style="width: 13px; height: 13px;" />
                                        Hadir (Selesai)
                                    </span>
                                @elseif ($att && $att->status === 'in_meeting')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #b45309;">
                                        <x-filament::icon icon="heroicon-s-radio" style="width: 13px; height: 13px;" />
                                        Sedang Rapat
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #f1f5f9; color: #64748b;">
                                        <x-filament::icon icon="heroicon-s-x-circle" style="width: 13px; height: 13px;" />
                                        Belum Hadir
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($att?->meet_in_at)
                                    <div style="font-weight: 700; color: #047857;">
                                        {{ $att->meet_in_at->format('H:i:s') }} WIB
                                    </div>
                                    <div style="font-size: 10px; color: #6b7280;">
                                        {{ $att->meet_in_at->format('d M Y') }}
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($att?->meet_out_at)
                                    <div style="font-weight: 700; color: #1d4ed8;">
                                        {{ $att->meet_out_at->format('H:i:s') }} WIB
                                    </div>
                                    <div style="font-size: 10px; color: #6b7280;">
                                        {{ $att->meet_out_at->format('d M Y') }}
                                    </div>
                                @elseif ($att && $att->status === 'in_meeting')
                                    <span style="font-size: 11px; font-weight: 700; color: #d97706;">Berlangsung</span>
                                @else
                                    <span style="color: #9ca3af;">-</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight: 800; font-size: 13px; color: {{ $att?->duration_seconds ? '#2563eb' : '#9ca3af' }};">
                                    {{ $durationFormatted }}
                                </span>
                            </td>
                            <td>
                                @if ($att?->report_notes)
                                    <div style="background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.06); border-radius: 8px; padding: 8px 12px; font-size: 12px; color: #374151; line-height: 1.4;" class="dark:!bg-white/5 dark:!border-white/10 dark:!text-gray-200">
                                        {{ $att->report_notes }}
                                    </div>
                                @else
                                    <span style="color: #9ca3af; font-size: 12px; font-style: italic;">Tidak ada catatan</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if ($photo)
                                    <a href="{{ asset('storage/' . $photo) }}" target="_blank" style="display: inline-block; position: relative; overflow: hidden; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); box-shadow: 0 1px 3px rgba(0,0,0,0.05);" title="Klik untuk memperbesar">
                                        <img src="{{ asset('storage/' . $photo) }}" alt="Foto Bukti" style="width: 54px; height: 54px; object-fit: cover; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'" />
                                    </a>
                                @else
                                    <span style="color: #9ca3af; font-size: 11px;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 32px 16px; color: #9ca3af;">
                                <x-filament::icon icon="heroicon-o-users" style="width: 36px; height: 36px; margin: 0 auto 8px; color: #d1d5db;" />
                                <div>Belum ada peserta yang didaftarkan pada meeting ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
