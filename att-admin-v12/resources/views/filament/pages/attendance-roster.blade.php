<x-filament-panels::page>
    <style>
        .roster-page-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .roster-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .roster-card {
            background: #1e293b;
            border-color: #334155;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
        }
        @media (min-width: 640px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .kpi-card {
            background: #1e293b;
            border-color: #334155;
        }

        .roster-table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #94a3b8;
            border-radius: 8px;
            background: #ffffff;
        }
        .dark .roster-table-container {
            border-color: #475569;
            background: #0f172a;
        }

        .roster-bordered-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            color: #1e293b;
            min-width: max-content;
        }
        .dark .roster-bordered-table {
            color: #f1f5f9;
        }

        .roster-bordered-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border-right: 1px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
            white-space: nowrap;
        }
        .dark .roster-bordered-table th {
            background: #1e293b;
            color: #f8fafc;
            border-right-color: #334155;
            border-bottom-color: #475569;
        }

        .roster-bordered-table th.weekend-header {
            background: #fef2f2;
            color: #991b1b;
        }
        .dark .roster-bordered-table th.weekend-header {
            background: #451a1a;
            color: #fca5a5;
        }

        .roster-bordered-table td {
            padding: 8px 10px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .dark .roster-bordered-table td {
            border-right-color: #334155;
            border-bottom-color: #334155;
        }

        .roster-bordered-table th:last-child,
        .roster-bordered-table td:last-child {
            border-right: none;
        }

        .roster-bordered-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .dark .roster-bordered-table tbody tr:nth-child(even) {
            background: #182234;
        }

        .roster-bordered-table tbody tr:hover {
            background: #f1f5f9 !important;
        }
        .dark .roster-bordered-table tbody tr:hover {
            background: #1e293b !important;
        }

        .roster-bordered-table .sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
            background: #ffffff;
            border-right: 2px solid #94a3b8 !important;
            min-width: 260px;
            max-width: 280px;
        }
        .dark .roster-bordered-table .sticky-col {
            background: #0f172a;
            border-right-color: #475569 !important;
        }
        .roster-bordered-table thead .sticky-col {
            background: #f1f5f9;
            z-index: 20;
        }
        .dark .roster-bordered-table thead .sticky-col {
            background: #1e293b;
        }

        .roster-cell-clickable {
            cursor: pointer;
            border-radius: 6px;
            padding: 4px;
            transition: all 0.15s ease-in-out;
            min-height: 52px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .roster-cell-clickable:hover {
            background: #e0e7ff;
            outline: 2px solid #6366f1;
        }
        .dark .roster-cell-clickable:hover {
            background: #312e81;
            outline-color: #818cf8;
        }

        .att-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .att-badge-present {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .att-badge-late {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .att-badge-absent {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .att-badge-leave {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .att-badge-permit {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }

        .time-pill {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #334155;
            font-family: monospace;
            font-weight: 600;
        }
        .dark .time-pill {
            color: #cbd5e1;
        }
    </style>

    @php
        $viewData = $this->getViewData();
        $employees = $viewData['employees'];
        $attendances = $viewData['attendances'];
        $daysInPeriod = $viewData['daysInPeriod'];
        $startDate = $viewData['startDate'];
        $endDate = $viewData['endDate'];
        $summary = $viewData['summary'];
        $pagination = $viewData['pagination'];
    @endphp

    <div class="roster-page-wrapper">
        {{-- KPI TOP SUMMARY CARDS --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Karyawan</div>
                    <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ number_format($viewData['totalEmployees']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Sesuai filter aktif</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Total Hadir (On-Time)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">{{ number_format($summary['total_present']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Periode terpilih</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-check-circle" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase;">Total Terlambat (Late)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ number_format($summary['total_late']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Check-in melebihi jadwal</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase;">Izin / Cuti / Lainnya</div>
                    <div style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ number_format($summary['total_leave']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Alpha: {{ number_format($summary['total_absent']) }}</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-document-text" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- FILTER FORM CARD --}}
        <div class="roster-card">
            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
            <div style="margin-top: 8px; font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <x-filament::icon icon="heroicon-o-information-circle" style="width: 16px; height: 16px;" />
                <span>Rentang kalender roster menampilkan maksimal <strong>31 hari</strong>. Klik pada sel kehadiran untuk melihat rincian aktivitas & rute live tracking.</span>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="roster-card">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-table-cells" style="width: 20px; height: 20px; color: #4f46e5;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Matriks Kehadiran Harian (Attendance Roster)</span>
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                        {{ number_format($viewData['totalEmployees']) }} Karyawan
                    </span>
                </div>

                {{-- Live Search Input --}}
                <div style="min-width: 280px;">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, NIK, area, prinsiple..."
                        style="width: 100%; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;"
                    />
                </div>
            </div>

            <div class="roster-table-container">
                <table class="roster-bordered-table">
                    <thead>
                        <tr>
                            <th class="sticky-col" style="text-align: left;">Karyawan</th>
                            @for ($d = 1; $d <= $daysInPeriod; $d++)
                                @php
                                    $date = $startDate->copy()->addDays($d - 1);
                                    $isWeekend = in_array($date->dayOfWeek, [0, 6]); // Sun or Sat
                                @endphp
                                <th class="{{ $isWeekend ? 'weekend-header' : '' }}" style="text-align: center; min-width: 125px;">
                                    <div style="font-weight: 800; font-size: 12px;">{{ $date->format('d M') }}</div>
                                    <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; opacity: 0.85;">
                                        {{ $date->translatedFormat('l') }}
                                    </div>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=4f46e5&color=fff&size=64';
                                if (!empty($employee->photo)) {
                                    try {
                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                                            $photoUrl = asset('storage/' . $employee->photo);
                                        }
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <tr>
                                <td class="sticky-col">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img
                                            src="{{ $photoUrl }}"
                                            alt="{{ $employee->full_name }}"
                                            style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;"
                                            loading="lazy"
                                        />
                                        <div style="min-width: 0; flex: 1;">
                                            <div style="font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $employee->full_name }}">
                                                {{ $employee->full_name }}
                                            </div>
                                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                                                NIK: {{ $employee->employee_no ?? '-' }}
                                            </div>
                                            <div style="font-size: 10px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                                                <span style="font-weight: 600; color: #4338ca;">{{ $employee->position_name ?? 'N/A' }}</span> &bull; 
                                                <span>{{ $employee->branch_name ?? ($employee->principal_name ?? '-') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                @for ($d = 1; $d <= $daysInPeriod; $d++)
                                    @php
                                        $dateStr = $startDate->copy()->addDays($d - 1)->toDateString();
                                        $dateObj = $startDate->copy()->addDays($d - 1);
                                        $isWeekend = in_array($dateObj->dayOfWeek, [0, 6]);
                                        
                                        $empAtts = $attendances->get($employee->id);
                                        $att = $empAtts ? $empAtts->firstWhere('attendance_date', $dateStr) : null;
                                    @endphp
                                    <td style="text-align: center; background: {{ $isWeekend ? '#fdf2f2' : 'inherit' }};">
                                        @if ($att)
                                            <div class="roster-cell-clickable" wire:click="mountAction('viewDetails', { employee_id: {{ $employee->id }}, date: '{{ $dateStr }}' })" title="Klik untuk rincian absensi">
                                                <div>
                                                    @if ($att->status === 'present')
                                                        <span class="att-badge att-badge-present">Hadir</span>
                                                    @elseif ($att->status === 'late')
                                                        <span class="att-badge att-badge-late">Telat</span>
                                                    @elseif ($att->status === 'absent')
                                                        <span class="att-badge att-badge-absent">Alpha</span>
                                                    @elseif ($att->status === 'leave')
                                                        <span class="att-badge att-badge-leave">Cuti</span>
                                                    @elseif ($att->status === 'permit')
                                                        <span class="att-badge att-badge-permit">Izin</span>
                                                    @else
                                                        <span class="att-badge att-badge-permit">{{ ucfirst($att->status) }}</span>
                                                    @endif
                                                </div>

                                                @if (!empty($att->checkin_at))
                                                    <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 2px; align-items: center;">
                                                        <div class="time-pill">
                                                            <span style="color: #059669; font-weight: 700;">In:</span>
                                                            <span>{{ Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta')->format('H:i') }}</span>
                                                        </div>
                                                        @if (!empty($att->checkout_at))
                                                            <div class="time-pill">
                                                                <span style="color: #dc2626; font-weight: 700;">Out:</span>
                                                                <span>{{ Carbon::parse($att->checkout_at)->timezone('Asia/Jakarta')->format('H:i') }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div style="min-height: 48px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px;">
                                                -
                                            </div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInPeriod + 1 }}" style="padding: 40px; text-align: center; color: #64748b;">
                                    Tidak ada data karyawan yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION TOOLBAR --}}
            @if ($pagination['total_pages'] > 1 || $viewData['totalEmployees'] > 0)
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                    <div>
                        Menampilkan <strong style="color: #0f172a;">{{ $pagination['from'] }}</strong> - <strong style="color: #0f172a;">{{ $pagination['to'] }}</strong> dari <strong style="color: #0f172a;">{{ number_format($viewData['totalEmployees']) }}</strong> karyawan
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span>Per halaman:</span>
                            <select wire:model.live="perPage" style="padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button
                                type="button"
                                wire:click="previousPage"
                                @if ($pagination['page'] <= 1) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] <= 1 ? '0.4' : '1' }};"
                            >
                                &laquo; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 4px;">
                                Halaman {{ $pagination['page'] }} dari {{ $pagination['total_pages'] }}
                            </span>

                            <button
                                type="button"
                                wire:click="nextPage({{ $pagination['total_pages'] }})"
                                @if ($pagination['page'] >= $pagination['total_pages']) disabled @endif
                                style="padding: 6px 12px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] >= $pagination['total_pages'] ? '0.4' : '1' }};"
                            >
                                Selanjutnya &raquo;
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
