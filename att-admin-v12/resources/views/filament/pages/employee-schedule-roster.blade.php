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
        {{-- TABS NAVIGATION --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 8px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-slate-800 dark:border-slate-700">
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button"
                    wire:click="setActiveTab('roster')"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; {{ $activeTab === 'roster' ? 'background: #0f52ba; color: #ffffff; box-shadow: 0 2px 6px rgba(15,82,186,0.3);' : 'background: transparent; color: #64748b;' }}">
                    <x-filament::icon icon="heroicon-o-calendar-days" style="width: 18px; height: 18px;" />
                    <span>Jadwal Roster (Kalender)</span>
                </button>

                <button type="button"
                    wire:click="setActiveTab('working_groups')"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; {{ $activeTab === 'working_groups' ? 'background: #0f52ba; color: #ffffff; box-shadow: 0 2px 6px rgba(15,82,186,0.3);' : 'background: transparent; color: #64748b;' }}">
                    <x-filament::icon icon="heroicon-o-user-group" style="width: 18px; height: 18px;" />
                    <span>Working Groups</span>
                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 800; {{ $activeTab === 'working_groups' ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: #e2e8f0; color: #334155;' }}">
                        {{ $this->workingGroups->count() }}
                    </span>
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                @if($activeTab === 'working_groups')
                    <a href="{{ \App\Filament\Resources\WorkingGroupResource::getUrl('create') }}"
                       style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; background: #059669; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 6px rgba(5,150,105,0.25);">
                        <x-filament::icon icon="heroicon-o-plus" style="width: 16px; height: 16px;" />
                        <span>+ Buat Working Group Baru</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($activeTab === 'roster')
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
                                            <div style="margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                                                <button
                                                    type="button"
                                                    wire:click="mountAction('deleteEmployeeSchedules', { employee_id: {{ $employee->id }}, employee_name: '{{ addslashes($employee->full_name) }}' })"
                                                    style="background: transparent; border: 1px solid #fecaca; cursor: pointer; padding: 2px 6px; border-radius: 4px; color: #dc2626; display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; transition: all 0.2s;"
                                                    onmouseover="this.style.background='#fee2e2'"
                                                    onmouseout="this.style.background='transparent'"
                                                    title="Hapus seluruh jadwal {{ $employee->full_name }} pada periode aktif"
                                                >
                                                    <x-filament::icon icon="heroicon-m-trash" style="width: 12px; height: 12px;" />
                                                    <span>Hapus Jadwal</span>
                                                </button>
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
        @else
            {{-- WORKING GROUPS LIST CARD --}}
            <div class="roster-card">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                            <x-filament::icon icon="heroicon-o-user-group" style="width: 22px; height: 22px;" />
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 800; color: #0f172a;" class="dark:text-white">Daftar Working Groups (Master Pola Kerja)</div>
                            <div style="font-size: 12px; color: #64748b;">Kelola master grup pola kerja, aturan hari kerja, dan generate jadwal roster karyawan</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        {{-- Search Box --}}
                        <div style="position: relative; min-width: 260px;">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="wgSearch"
                                placeholder="Cari nama grup, area, sub area..."
                                style="width: 100%; padding: 8px 12px 8px 36px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a;"
                                class="dark:bg-slate-900 dark:border-slate-700 dark:text-white"
                            />
                            <div style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">
                                <x-filament::icon icon="heroicon-o-magnifying-glass" style="width: 16px; height: 16px;" />
                            </div>
                        </div>

                        <a href="{{ \App\Filament\Resources\WorkingGroupResource::getUrl('create') }}"
                           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; background: #059669; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 6px rgba(5,150,105,0.25);">
                            <x-filament::icon icon="heroicon-o-plus" style="width: 16px; height: 16px;" />
                            <span>+ Buat Working Group Baru</span>
                        </a>
                    </div>
                </div>

                {{-- Table of Working Groups --}}
                <div class="roster-table-container">
                    <table class="roster-bordered-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Working Group</th>
                                <th>Prinsiple</th>
                                <th>Area / Cabang</th>
                                <th style="text-align: center;">Tgl Berlaku</th>
                                <th>Shift & Jam Kerja</th>
                                <th>Pola Hari Kerja</th>
                                <th style="text-align: center;">Total Anggota</th>
                                <th style="text-align: center; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->workingGroups as $idx => $wg)
                                <tr>
                                    <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $idx + 1 }}</td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 13.5px; color: #0f172a;" class="dark:text-white">{{ $wg->name }}</div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                            Dibuat: {{ $wg->created_at ? $wg->created_at->format('d M Y H:i') : '-' }}
                                            @if($wg->creator)
                                                &bull; oleh {{ $wg->creator->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e40af;">{{ $wg->principal?->name ?? 'Semua Prinsiple' }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $wg->branch?->name ?? $wg->area ?? '-' }}</div>
                                        @if($wg->sub_area)
                                            <div style="font-size: 10.5px; color: #64748b;">Sub: {{ $wg->sub_area }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($wg->data_applied_date)
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; background: #e0f2fe; color: #0369a1;">
                                                {{ $wg->data_applied_date->format('d M Y') }}
                                            </span>
                                        @else
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 12px; color: #0f172a;" class="dark:text-white">
                                            {{ $wg->defaultShift?->name ?? 'Default Shift' }}
                                        </div>
                                        @if($wg->defaultShift && $wg->defaultShift->start_time && $wg->defaultShift->end_time)
                                            <div style="font-family: monospace; font-size: 11px; color: #059669; font-weight: 700;">
                                                {{ substr($wg->defaultShift->start_time, 0, 5) }} - {{ substr($wg->defaultShift->end_time, 0, 5) }}
                                            </div>
                                        @endif
                                        @if($wg->defaultWorkLocation)
                                            <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                                                📍 {{ $wg->defaultWorkLocation->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $activeDays = $wg->rules->where('is_active', true)->pluck('day_of_week')->toArray();
                                            $dayLabels = [
                                                'Monday' => 'Sen',
                                                'Tuesday' => 'Sel',
                                                'Wednesday' => 'Rab',
                                                'Thursday' => 'Kam',
                                                'Friday' => 'Jum',
                                                'Saturday' => 'Sab',
                                                'Sunday' => 'Min',
                                            ];
                                        @endphp
                                        <div style="display: flex; gap: 3px; flex-wrap: wrap;">
                                            @foreach($dayLabels as $dEng => $dInd)
                                                @php $isActive = in_array($dEng, $activeDays); @endphp
                                                <span style="display: inline-block; padding: 1px 5px; border-radius: 4px; font-size: 10px; font-weight: 700; {{ $isActive ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0;' }}">
                                                    {{ $dInd }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button"
                                            wire:click="viewWorkingGroupMembers({{ $wg->id }})"
                                            style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 8px; background: #e0e7ff; color: #3730a3; font-size: 12px; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s;"
                                            title="Klik untuk melihat anggota">
                                            <x-filament::icon icon="heroicon-o-users" style="width: 14px; height: 14px;" />
                                            <span>{{ number_format($wg->members_count) }} Karyawan</span>
                                        </button>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                            {{-- Re-generate Roster Action --}}
                                            <button type="button"
                                                wire:click="regenerateWorkingGroup({{ $wg->id }})"
                                                wire:confirm="Yakin ingin meng-generate ulang jadwal roster karyawan untuk '{{ $wg->name }}' sampai akhir tahun?"
                                                style="padding: 6px; border-radius: 6px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; cursor: pointer;"
                                                title="Generate Ulang Jadwal Roster">
                                                <x-filament::icon icon="heroicon-o-arrow-path" style="width: 16px; height: 16px;" />
                                            </button>

                                            {{-- Edit Action --}}
                                            <a href="{{ \App\Filament\Resources\WorkingGroupResource::getUrl('edit', ['record' => $wg->id]) }}"
                                               style="padding: 6px; border-radius: 6px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; justify-content: center;"
                                               title="Edit Pola Kerja">
                                                <x-filament::icon icon="heroicon-o-pencil-square" style="width: 16px; height: 16px;" />
                                            </a>

                                            {{-- Delete Action --}}
                                            <button type="button"
                                                wire:click="deleteWorkingGroup({{ $wg->id }})"
                                                wire:confirm="Yakin ingin menghapus Working Group '{{ $wg->name }}'? Aturan pola kerja ini akan dihapus."
                                                style="padding: 6px; border-radius: 6px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; cursor: pointer;"
                                                title="Hapus Working Group">
                                                <x-filament::icon icon="heroicon-o-trash" style="width: 16px; height: 16px;" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 48px 16px; color: #64748b;">
                                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                            <x-filament::icon icon="heroicon-o-user-group" style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-weight: 700; font-size: 15px; color: #0f172a;" class="dark:text-white">Belum Ada Working Group</div>
                                        <div style="font-size: 12.5px; margin-top: 4px; max-width: 400px; margin-left: auto; margin-right: auto;">
                                            Belum ada pola kerja kelompok yang dibuat. Klik tombol di bawah untuk membuat Working Group baru.
                                        </div>
                                        <div style="margin-top: 16px;">
                                            <a href="{{ \App\Filament\Resources\WorkingGroupResource::getUrl('create') }}"
                                               style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 8px; background: #0f52ba; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none;">
                                                <x-filament::icon icon="heroicon-o-plus" style="width: 16px; height: 16px;" />
                                                <span>Buat Working Group Baru</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL PREVIEW ANGGOTA WORKING GROUP --}}
    @if($this->viewingWorkingGroup)
        <div style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 16px;" wire:click.self="closeWorkingGroupModal">
            <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); width: 100%; max-width: 850px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:border-slate-700">
                {{-- Modal Header --}}
                <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;" class="dark:bg-slate-900 dark:border-slate-700">
                    <div>
                        <div style="font-size: 16px; font-weight: 800; color: #0f172a;" class="dark:text-white">
                            Anggota: {{ $this->viewingWorkingGroup->name }}
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            Prinsiple: <strong>{{ $this->viewingWorkingGroup->principal?->name ?? 'Semua' }}</strong> &bull;
                            Area: <strong>{{ $this->viewingWorkingGroup->branch?->name ?? $this->viewingWorkingGroup->area ?? '-' }}</strong> &bull;
                            Total: <strong>{{ $this->viewingWorkingGroup->members->count() }} Karyawan</strong>
                        </div>
                    </div>
                    <button type="button" wire:click="closeWorkingGroupModal" style="background: transparent; border: none; cursor: pointer; color: #64748b; padding: 6px; border-radius: 6px;">
                        <x-filament::icon icon="heroicon-o-x-mark" style="width: 22px; height: 22px;" />
                    </button>
                </div>

                {{-- Modal Body --}}
                <div style="padding: 20px; overflow-y: auto; flex: 1;">
                    <table class="roster-bordered-table" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th>NIK / ID</th>
                                <th>Nama Karyawan</th>
                                <th>Posisi / Jabatan</th>
                                <th>Cabang</th>
                                <th>Shift Khusus</th>
                                <th>Toko Kunjungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->viewingWorkingGroup->members as $mIdx => $member)
                                <tr>
                                    <td style="text-align: center; color: #64748b;">{{ $mIdx + 1 }}</td>
                                    <td style="font-family: monospace; font-weight: 700;">{{ $member->employee?->nik ?? $member->employee?->id ?? '-' }}</td>
                                    <td style="font-weight: 700; color: #0f172a;" class="dark:text-white">{{ $member->employee?->full_name ?? $member->employee?->first_name ?? '-' }}</td>
                                    <td>{{ $member->employee?->position?->name ?? '-' }}</td>
                                    <td>{{ $member->employee?->branch?->name ?? '-' }}</td>
                                    <td>{{ $member->shift?->name ?? 'Ikut Default' }}</td>
                                    <td>{{ $member->firstVisitStore?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">
                                        Belum ada karyawan yang terdaftar di Working Group ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Modal Footer --}}
                <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;" class="dark:bg-slate-900 dark:border-slate-700">
                    <div style="font-size: 12px; color: #64748b;">
                        Tgl Berlaku: <strong>{{ $this->viewingWorkingGroup->data_applied_date ? $this->viewingWorkingGroup->data_applied_date->format('d M Y') : '-' }}</strong>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button"
                            wire:click="regenerateWorkingGroup({{ $this->viewingWorkingGroup->id }})"
                            wire:confirm="Yakin ingin generate ulang jadwal anggota grup ini?"
                            style="padding: 7px 14px; border-radius: 8px; background: #059669; color: #ffffff; font-size: 12.5px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <x-filament::icon icon="heroicon-o-arrow-path" style="width: 15px; height: 15px;" />
                            <span>Re-Generate Roster</span>
                        </button>
                        <button type="button" wire:click="closeWorkingGroupModal" style="padding: 7px 16px; border-radius: 8px; background: #e2e8f0; color: #334155; font-size: 12.5px; font-weight: 700; border: none; cursor: pointer;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
