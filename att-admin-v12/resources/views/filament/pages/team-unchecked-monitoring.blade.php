<x-filament-panels::page>
    <style>
        .matrix-table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }
        .matrix-cell-clickable {
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .matrix-cell-clickable:hover {
            transform: scale(1.05);
            font-weight: 700;
        }
        .date-chip {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 9999px;
            margin: 2px;
        }
    </style>

    @php
        $matrix = $this->getMatrixData();
        $details = $this->getFilteredDetailData();
        $summary = $matrix['summary_info'];
        $allPrincipals = \App\Models\Principal::orderBy('name')->get(['id', 'name']);
        $allBranches = \App\Models\Branch::orderBy('name')->get(['id', 'name']);

        // Hitung metrik ringkasan
        $todayUncheckedCount = collect($summary['employees'])->where('is_today_unchecked', true)->count();
        $ge3DaysCount = collect($summary['employees'])->where('missed_count_7days', '>=', 3)->count();
        $neverAttendedCount = collect($summary['employees'])->where('days_since_last', -1)->count();
    @endphp

    {{-- Top Metric Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Belum Check-in 7 Hari --}}
        <div class="p-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Belum Check-In (7 Hari)</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_unchecked_employees']) }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Dari total {{ number_format($summary['total_active_employees']) }} karyawan aktif</span>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-danger-50 text-danger-600 dark:bg-danger-950/40 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-o-user-minus" class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Card 2: Belum Check-in Hari Ini --}}
        <div class="p-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">Belum Check-In Hari Ini</span>
                    <span class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($todayUncheckedCount) }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $summary['today_formatted'] }}</span>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-warning-50 text-warning-600 dark:bg-warning-950/40 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Card 3: >= 3 Hari Tidak Hadir --}}
        <div class="p-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">&ge; 3 Hari Tidak Hadir</span>
                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($ge3DaysCount) }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Perlu perhatian / follow-up</span>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Card 4: Belum Pernah Hadir --}}
        <div class="p-4 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Belum Pernah Hadir</span>
                    <span class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ number_format($neverAttendedCount) }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tidak ada riwayat presensi</span>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <x-filament::icon icon="heroicon-o-no-symbol" class="w-6 h-6" />
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar Section --}}
    <x-filament::section>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 flex-1">
                {{-- Filter Prinsiple --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Filter Prinsiple</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedPrincipalId">
                            <option value="">-- Semua Prinsiple --</option>
                            @foreach ($allPrincipals as $p)
                                <option value="{{ (string)$p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Filter Area / Cabang --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Filter Area / Cabang</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedBranchId">
                            <option value="">-- Semua Area / Cabang --</option>
                            @foreach ($allBranches as $b)
                                <option value="{{ (string)$b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                {{-- Pencarian Cepat --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Cari Karyawan / NIK / Jabatan</label>
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="Ketik nama atau NIK..."
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- Reset Filter Button --}}
            <div class="flex items-end gap-2 pt-2 lg:pt-0">
                <x-filament::button wire:click="resetAllFilters" color="gray" icon="heroicon-o-arrow-path" size="sm">
                    Reset Filter
                </x-filament::button>
            </div>
        </div>

        {{-- Quick Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-white/5">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Status:</span>
            
            <button
                type="button"
                wire:click="$set('quickFilter', 'all')"
                class="px-3 py-1 text-xs font-semibold rounded-lg transition {{ $quickFilter === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                Semua (7 Hari)
            </button>

            <button
                type="button"
                wire:click="$set('quickFilter', 'today')"
                class="px-3 py-1 text-xs font-semibold rounded-lg transition {{ $quickFilter === 'today' ? 'bg-warning-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                Belum Check-In Hari Ini
            </button>

            <button
                type="button"
                wire:click="$set('quickFilter', 'ge3')"
                class="px-3 py-1 text-xs font-semibold rounded-lg transition {{ $quickFilter === 'ge3' ? 'bg-rose-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                &ge; 3 Hari Tidak Hadir
            </button>

            <button
                type="button"
                wire:click="$set('quickFilter', 'never')"
                class="px-3 py-1 text-xs font-semibold rounded-lg transition {{ $quickFilter === 'never' ? 'bg-slate-700 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                Belum Pernah Hadir
            </button>
        </div>
    </x-filament::section>

    {{-- SECTION 1: MATRIKS PRINSIPLE VS AREA (SESUAI GAMBAR 1) --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    <span class="text-base font-bold text-gray-900 dark:text-white">Matriks Tim Belum Check-In (Prinsiple vs Area)</span>
                </div>
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                    Klik pada angka cell untuk memfilter detail karyawan
                </span>
            </div>
        </x-slot>

        <div class="matrix-table-wrapper rounded-lg ring-1 ring-gray-200 dark:ring-white/10 overflow-hidden">
            <table class="w-full text-left divide-y table-auto divide-gray-200 dark:divide-white/5 text-sm" style="border-collapse: collapse;">
                {{-- Header Columns (Area) --}}
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider border-r border-gray-200 dark:border-white/5 min-w-[200px] sticky left-0 bg-gray-50 dark:bg-gray-900 z-10">
                            Prinsiple
                        </th>
                        @foreach ($matrix['columns'] as $colId => $colName)
                            <th class="py-3 px-4 font-bold text-center text-gray-900 dark:text-white uppercase text-xs tracking-wider border-r border-gray-200 dark:border-white/5 min-w-[120px]">
                                {{ $colName }}
                            </th>
                        @endforeach
                        <th class="py-3 px-4 font-bold text-center text-primary-600 dark:text-primary-400 uppercase text-xs tracking-wider min-w-[110px] bg-primary-50/50 dark:bg-primary-950/20">
                            Total
                        </th>
                    </tr>
                </thead>

                {{-- Rows (Prinsiple) --}}
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($matrix['rows'] as $index => $row)
                        @php
                            $isEven = ($index % 2 === 0);
                            $rowPId = (string)($row['principal_id'] ?? '0');
                            $isRowSelected = ($selectedCellPrincipalId === $rowPId);
                        @endphp
                        <tr class="{{ $isEven ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/60 dark:bg-white/[0.02]' }} hover:bg-gray-100/70 dark:hover:bg-white/5 transition">
                            {{-- Principal Name Header Cell --}}
                            <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white border-r border-gray-200 dark:border-white/5 sticky left-0 {{ $isEven ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-900' }} z-10">
                                <div class="flex items-center justify-between">
                                    <span>{{ $row['principal_name'] }}</span>
                                    <button
                                        type="button"
                                        wire:click="selectMatrixCell('{{ $rowPId }}', '', '{{ addslashes($row['principal_name']) }}', '')"
                                        title="Filter seluruh area untuk {{ $row['principal_name'] }}"
                                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 underline font-normal ml-2"
                                    >
                                        Pilih
                                    </button>
                                </div>
                            </td>

                            {{-- Branch Values --}}
                            @foreach ($matrix['columns'] as $colId => $colName)
                                @php
                                    $colBId = (string)$colId;
                                    $val = $row['branches'][$colId] ?? 0;
                                    $isCellActive = ($selectedCellPrincipalId === $rowPId && $selectedCellBranchId === $colBId);
                                @endphp
                                <td class="py-3 px-4 text-center border-r border-gray-200 dark:border-white/5 {{ $isCellActive ? 'bg-primary-100 dark:bg-primary-950/60 ring-2 ring-primary-500 font-bold' : '' }}">
                                    @if ($val > 0)
                                        <button
                                            type="button"
                                            wire:click="selectMatrixCell('{{ $rowPId }}', '{{ $colBId }}', '{{ addslashes($row['principal_name']) }}', '{{ addslashes($colName) }}')"
                                            class="matrix-cell-clickable inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-lg {{ $val >= 3 ? 'bg-danger-100 text-danger-800 dark:bg-danger-950/50 dark:text-danger-300' : 'bg-warning-100 text-warning-800 dark:bg-warning-950/50 dark:text-warning-300' }}"
                                            title="Klik untuk melihat {{ $val }} karyawan"
                                        >
                                            {{ $val }}
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">-</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Row Total --}}
                            <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white bg-primary-50/30 dark:bg-primary-950/10">
                                @if ($row['total_row'] > 0)
                                    <button
                                        type="button"
                                        wire:click="selectMatrixCell('{{ $rowPId }}', '', '{{ addslashes($row['principal_name']) }}', '')"
                                        class="matrix-cell-clickable text-xs font-extrabold text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        {{ $row['total_row'] }}
                                    </button>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600 text-xs">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($matrix['columns']) + 2 }}" class="py-6 text-center text-gray-500">
                                Tidak ada data matriks yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Column Totals (Footer) --}}
                @if (count($matrix['rows']) > 0)
                    <tfoot class="bg-gray-100 dark:bg-white/10 font-bold border-t-2 border-gray-300 dark:border-white/20">
                        <tr>
                            <td class="py-3 px-4 text-gray-900 dark:text-white uppercase text-xs tracking-wider border-r border-gray-200 dark:border-white/5 sticky left-0 bg-gray-100 dark:bg-gray-800 z-10">
                                Total Per Area
                            </td>
                            @foreach ($matrix['columns'] as $colId => $colName)
                                @php
                                    $colBId = (string)$colId;
                                    $colTotal = $matrix['column_totals'][$colId] ?? 0;
                                    $isColActive = ($selectedCellBranchId === $colBId && $selectedCellPrincipalId === null);
                                @endphp
                                <td class="py-3 px-4 text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-white/5 {{ $isColActive ? 'bg-primary-200 dark:bg-primary-900/60 font-bold' : '' }}">
                                    @if ($colTotal > 0)
                                        <button
                                            type="button"
                                            wire:click="selectMatrixCell('', '{{ $colBId }}', '', '{{ addslashes($colName) }}')"
                                            class="matrix-cell-clickable text-xs font-bold text-gray-900 dark:text-white hover:text-primary-600 hover:underline"
                                            title="Filter seluruh prinsiple di {{ $colName }}"
                                        >
                                            {{ $colTotal }}
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">0</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="py-3 px-4 text-center font-extrabold text-primary-700 dark:text-primary-300 text-sm bg-primary-100/70 dark:bg-primary-950/40">
                                {{ $matrix['grand_total'] }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>

    {{-- ACTIVE FILTER BANNER (Jika Cell Matriks Diklik) --}}
    @if (!empty($selectedCellPrincipalId) || !empty($selectedCellBranchId))
        <div class="flex items-center justify-between p-3.5 rounded-xl bg-primary-50 border border-primary-200 text-primary-900 dark:bg-primary-950/40 dark:border-primary-800 dark:text-primary-200 shadow-sm">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-funnel" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                <span class="text-sm font-semibold">
                    Filter Aktif Matriks:
                    <strong>{{ $selectedCellPrincipalName ?: 'Semua Prinsiple' }}</strong>
                    &bull;
                    <strong>{{ $selectedCellBranchName ?: 'Semua Area' }}</strong>
                    <span class="ml-1 text-xs opacity-80">({{ count($details) }} Karyawan Ditemukan)</span>
                </span>
            </div>
            <x-filament::button wire:click="resetCellFilter" color="danger" size="xs" icon="heroicon-o-x-mark">
                Hapus Filter Matriks
            </x-filament::button>
        </div>
    @endif

    {{-- SECTION 2: TABEL DETAIL KARYAWAN (SESUAI GAMBAR 2) --}}
    <x-filament::section id="detail-section">
        <x-slot name="heading">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-list-bullet" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    <span class="text-base font-bold text-gray-900 dark:text-white">Rincian Data Karyawan Belum Check-In</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300">
                        {{ count($details) }} Karyawan
                    </span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Rentang: {{ $summary['seven_days_range'] }}
                </span>
            </div>
        </x-slot>

        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-white/10 overflow-x-auto">
            <table class="w-full text-left divide-y table-auto divide-gray-200 dark:divide-white/5 text-sm" style="border-collapse: collapse;">
                {{-- Table Header Sesuai Gambar 2 --}}
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider min-w-[240px]">
                            Nama Karyawan
                        </th>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider min-w-[150px]">
                            Jabatan
                        </th>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider min-w-[160px]">
                            Prinsiple
                        </th>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider min-w-[140px]">
                            Area
                        </th>
                        <th class="py-3 px-4 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wider min-w-[280px]">
                            Tgl Tidak Check-in (7 Hari Terakhir)
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($details as $index => $emp)
                        @php
                            $isEven = ($index % 2 === 0);
                            $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($emp['full_name']) . '&background=7367F0&color=fff&size=64';
                            if (!empty($emp['photo'])) {
                                try {
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($emp['photo'])) {
                                        $photoUrl = asset('storage/' . $emp['photo']);
                                    }
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr class="{{ $isEven ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/60 dark:bg-white/[0.02]' }} hover:bg-gray-100/70 dark:hover:bg-white/5 transition">
                            {{-- Nama Karyawan --}}
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        src="{{ $photoUrl }}"
                                        alt="{{ $emp['full_name'] }}"
                                        class="w-9 h-9 rounded-full object-cover ring-1 ring-gray-200 dark:ring-white/10 shrink-0"
                                    />
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $emp['full_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">NIK: {{ $emp['employee_no'] }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Jabatan --}}
                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300 font-medium">
                                {{ $emp['position'] }}
                            </td>

                            {{-- Prinsiple --}}
                            <td class="py-3 px-4 text-gray-900 dark:text-white font-semibold">
                                {{ $emp['principal_name'] }}
                            </td>

                            {{-- Area --}}
                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $emp['branch_name'] }}
                                </span>
                            </td>

                            {{-- Tgl Tidak Check-In (7 Hari Terakhir) --}}
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($emp['missed_dates'] as $mDate)
                                        @if ($mDate['is_today'])
                                            <span class="date-chip bg-danger-100 text-danger-800 dark:bg-danger-950/60 dark:text-danger-300 ring-1 ring-danger-300 dark:ring-danger-800" title="{{ $mDate['full_date'] }} (Hari Ini)">
                                                <span class="w-1.5 h-1.5 rounded-full bg-danger-500 mr-1 animate-pulse"></span>
                                                {{ $mDate['formatted_date'] }} (Hari Ini)
                                            </span>
                                        @else
                                            <span class="date-chip bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-900" title="{{ $mDate['full_date'] }} ({{ $mDate['day_name'] }})">
                                                {{ $mDate['formatted_date'] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                    Total: <strong class="text-rose-600 dark:text-rose-400">{{ $emp['missed_count_7days'] }} Hari</strong> | Terakhir Hadir: {{ $emp['last_attendance_date'] }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="w-10 h-10 text-success-500 mb-2" />
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Tidak ada karyawan yang belum check-in pada kriteria ini.</span>
                                    <span class="text-xs text-gray-400 mt-1">Semua karyawan hadir tepat waktu atau filter tidak menghasilkan data.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
