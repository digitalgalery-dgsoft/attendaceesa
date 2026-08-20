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
            vertical-align: middle;
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

        .schedule-cell-clickable {
            cursor: pointer;
            border-radius: 6px;
            padding: 6px 8px;
            transition: all 0.15s ease-in-out;
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .schedule-cell-clickable:hover {
            background: #e0e7ff;
            outline: 2px solid #6366f1;
        }
        .dark .schedule-cell-clickable:hover {
            background: #312e81;
            outline-color: #818cf8;
        }

        .sched-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: fit-content;
        }
        .sched-badge-workday {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .sched-badge-remote {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .sched-badge-field {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .sched-badge-dayoff {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .time-pill {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #1e293b;
            font-family: monospace;
            font-weight: 700;
            margin-top: 3px;
        }
        .dark .time-pill {
            color: #f1f5f9;
        }

        .loc-pill {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .loc-pill {
            color: #94a3b8;
        }
    </style>

    @php
        $viewData = $this->getViewData();
        $employees = $viewData['employees'];
        $schedules = $viewData['schedules'];
        $holidayMap = $viewData['holidayMap'] ?? [];
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
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Memiliki jadwal di periode ini</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Shift Kerja (Workday)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">{{ number_format($summary['total_workday']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Total jadwal shift aktif</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-briefcase" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Hari Libur (Day Off)</div>
                    <div style="font-size: 26px; font-weight: 800; color: #64748b; margin-top: 4px;">{{ number_format($summary['total_dayoff']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Libur rutin / terjadwal</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-sun" style="width: 24px; height: 24px;" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase;">Variasi Shift</div>
                    <div style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ number_format($summary['unique_shifts']) }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Shift yang digunakan</div>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 24px; height: 24px;" />
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
                <span>Rentang kalender roster menampilkan maksimal <strong>31 hari</strong>. Hari libur (weekend / tanggal merah) otomatis diselaraskan dengan jam kerja departemen.</span>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="roster-card">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-filament::icon icon="heroicon-o-calendar-days" style="width: 20px; height: 20px; color: #059669;" />
                    <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Matriks Jadwal Kerja (Employee Schedule Roster)</span>
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #d1fae5; color: #065f46;">
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
                                    $isWeekend = in_array($date->dayOfWeek, [0, 6]);
                                    $isNatHoliday = isset($holidayMap[$date->toDateString()]);
                                @endphp
                                <th class="{{ ($isWeekend || $isNatHoliday) ? 'weekend-header' : '' }}" style="text-align: center; min-width: 135px;">
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
                                $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=059669&color=fff&size=64';
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
                                                <span style="font-weight: 600; color: #059669;">{{ $employee->position_name ?? 'N/A' }}</span> &bull; 
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
                                        $isNatHoliday = isset($holidayMap[$dateStr]);
                                        $isDeptWorkDay = \App\Filament\Resources\EmployeeSchedules\Pages\EmployeeScheduleRoster::isWorkingDay($dateObj, $employee->dept_working_days);
                                        
                                        $empScheds = $schedules->get($employee->id);
                                        $sched = $empScheds ? $empScheds->firstWhere('schedule_date', $dateStr) : null;
                                    @endphp
                                    <td style="background: {{ ($isWeekend || $isNatHoliday || !$isDeptWorkDay) ? '#fdf2f2' : 'inherit' }};">
                                        <div class="schedule-cell-clickable" wire:click="mountAction('editSchedule', { employee_id: {{ $employee->id }}, schedule_date: '{{ $dateStr }}' })" title="Klik untuk edit jadwal">
                                            @if ($isNatHoliday || !$isDeptWorkDay || ($sched && in_array($sched->schedule_type, ['dayoff', 'holiday'])))
                                                {{-- Non-working day / Weekend / Holiday --}}
                                                <div style="display: flex; align-items: center; justify-content: center; min-height: 48px;">
                                                    <span class="sched-badge sched-badge-dayoff">Libur</span>
                                                </div>
                                            @elseif ($sched && in_array($sched->schedule_type, ['workday', 'remote', 'field']))
                                                <div>
                                                    @if ($sched->schedule_type === 'workday')
                                                        <span class="sched-badge sched-badge-workday">Planned</span>
                                                    @elseif ($sched->schedule_type === 'remote')
                                                        <span class="sched-badge sched-badge-remote">Remote</span>
                                                    @else
                                                        <span class="sched-badge sched-badge-field">Field</span>
                                                    @endif
                                                </div>

                                                <div class="time-pill">
                                                    @if (!empty($sched->planned_start_at) && !empty($sched->planned_end_at))
                                                        {{ \Carbon\Carbon::parse($sched->planned_start_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($sched->planned_end_at)->format('H:i') }}
                                                    @elseif (!empty($sched->shift_start_time) && !empty($sched->shift_end_time))
                                                        {{ substr($sched->shift_start_time, 0, 5) }} - {{ substr($sched->shift_end_time, 0, 5) }}
                                                    @else
                                                        08:00 - 17:00
                                                    @endif
                                                </div>

                                                <div class="loc-pill" title="{{ $sched->work_location_name ?? ($sched->shift_name ?? 'Office') }}">
                                                    <x-filament::icon icon="heroicon-o-map-pin" style="width: 12px; height: 12px; flex-shrink: 0; color: #64748b;" />
                                                    <span style="overflow: hidden; text-overflow: ellipsis;">{{ $sched->work_location_name ?? ($sched->shift_name ?? 'Office') }}</span>
                                                </div>
                                            @else
                                                <div style="display: flex; align-items: center; justify-content: center; min-height: 48px; color: #94a3b8; font-size: 12px;">
                                                    -
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInPeriod + 1 }}" style="padding: 40px; text-align: center; color: #64748b;">
                                    Tidak ada data jadwal karyawan yang sesuai dengan kriteria filter.
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

    <x-filament-actions::modals />
</x-filament-panels::page>
