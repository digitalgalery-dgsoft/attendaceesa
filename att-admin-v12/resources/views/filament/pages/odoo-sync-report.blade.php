<x-filament-panels::page>
    @php
        $report = $this->getReportData();
        $batches = $this->getRecentBatches();
        $historyLogs = $this->getHistoryLogs();
    @endphp

    {{-- Filter & Control Header --}}
    <x-filament::section>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-1">
                <div class="w-full sm:w-80">
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">Pilih Batch Sinkronisasi</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedBatchId" class="w-full text-sm">
                            <option value="latest">-- Batch Terakhir (Default) --</option>
                            @foreach ($batches as $batchId => $label)
                                <option value="{{ $batchId }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if ($report['executed_at'])
                    <div class="mt-4 sm:mt-0 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/60 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div><strong>Waktu Eksekusi:</strong> {{ $report['executed_at'] }}</div>
                        <div><strong>Tipe Trigger:</strong> <span class="capitalize font-medium text-primary-600 dark:text-primary-400">{{ $report['trigger_type'] ?? 'Cron' }}</span></div>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button wire:click="$refresh" color="gray" icon="heroicon-o-arrow-path" size="sm">
                    Refresh Data
                </x-filament::button>
                <x-filament::button tag="a" href="{{ route('filament.admin.pages.odoo-sync') }}" color="primary" icon="heroicon-o-cog-6-tooth" size="sm">
                    Kelola Odoo Sync
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Top Metric Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: New --}}
        <div class="bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <x-filament::icon icon="heroicon-o-user-plus" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Total Employee New</p>
                <h3 class="text-2xl font-bold text-emerald-900 dark:text-emerald-200">{{ number_format($report['sum_new']) }}</h3>
            </div>
        </div>

        {{-- Card 2: Update --}}
        <div class="bg-blue-50/80 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Total Employee Update</p>
                <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-200">{{ number_format($report['sum_update']) }}</h3>
            </div>
        </div>

        {{-- Card 3: Resign --}}
        <div class="bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <x-filament::icon icon="heroicon-o-user-minus" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Total Employee Resign</p>
                <h3 class="text-2xl font-bold text-rose-900 dark:text-rose-200">{{ number_format($report['sum_resign']) }}</h3>
            </div>
        </div>

        {{-- Card 4: Total --}}
        <div class="bg-purple-50/80 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wider">Total Seluruh Employee</p>
                <h3 class="text-2xl font-bold text-purple-900 dark:text-purple-200">{{ number_format($report['sum_total_employees']) }}</h3>
            </div>
        </div>
    </div>

    {{-- Main 4-Column Layout (Matching User Mockup) --}}
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-800">
            
            {{-- Column 1: Data Employee New --}}
            <div class="pt-4 md:pt-0 md:px-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h4 class="font-bold text-base text-gray-800 dark:text-gray-200">Data Employee New</h4>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300">
                        {{ $report['sum_new'] }}
                    </span>
                </div>

                <div class="space-y-3 font-mono text-sm">
                    @forelse ($report['new_data'] as $item)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <span class="font-semibold text-gray-700 dark:text-gray-300" title="{{ $item['name'] }}">
                                {{ $item['code'] }} :
                            </span>
                            <span class="font-bold {{ $item['count'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-600' }}">
                                {{ $item['count'] }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada data.</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-800 flex justify-between items-center text-xs font-bold text-gray-600 dark:text-gray-400">
                    <span>TOTAL NEW :</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-mono text-sm">{{ $report['sum_new'] }}</span>
                </div>
            </div>

            {{-- Column 2: Data Employee Update --}}
            <div class="pt-4 md:pt-0 md:px-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <h4 class="font-bold text-base text-gray-800 dark:text-gray-200">Data Employee Update</h4>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                        {{ $report['sum_update'] }}
                    </span>
                </div>

                <div class="space-y-3 font-mono text-sm">
                    @forelse ($report['update_data'] as $item)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <span class="font-semibold text-gray-700 dark:text-gray-300" title="{{ $item['name'] }}">
                                {{ $item['code'] }} :
                            </span>
                            <span class="font-bold {{ $item['count'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-600' }}">
                                {{ $item['count'] }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada data.</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-800 flex justify-between items-center text-xs font-bold text-gray-600 dark:text-gray-400">
                    <span>TOTAL UPDATE :</span>
                    <span class="text-blue-600 dark:text-blue-400 font-mono text-sm">{{ $report['sum_update'] }}</span>
                </div>
            </div>

            {{-- Column 3: Data Employee Resign --}}
            <div class="pt-4 md:pt-0 md:px-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <h4 class="font-bold text-base text-gray-800 dark:text-gray-200">Data Employee Resign</h4>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300">
                        {{ $report['sum_resign'] }}
                    </span>
                </div>

                <div class="space-y-3 font-mono text-sm">
                    @forelse ($report['resign_data'] as $item)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <span class="font-semibold text-gray-700 dark:text-gray-300" title="{{ $item['name'] }}">
                                {{ $item['code'] }} :
                            </span>
                            <span class="font-bold {{ $item['count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-gray-600' }}">
                                {{ $item['count'] }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada data.</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-800 flex justify-between items-center text-xs font-bold text-gray-600 dark:text-gray-400">
                    <span>TOTAL RESIGN :</span>
                    <span class="text-rose-600 dark:text-rose-400 font-mono text-sm">{{ $report['sum_resign'] }}</span>
                </div>
            </div>

            {{-- Column 4: Total Seluruh Employee per Company --}}
            <div class="pt-4 md:pt-0 md:px-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        <h4 class="font-bold text-base text-gray-800 dark:text-gray-200">Total Employee</h4>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">
                        {{ $report['sum_total_employees'] }}
                    </span>
                </div>

                <div class="space-y-3 font-mono text-sm">
                    @forelse ($report['total_employee_data'] as $item)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <span class="font-semibold text-gray-700 dark:text-gray-300" title="{{ $item['name'] }}">
                                {{ $item['code'] }} :
                            </span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($item['count']) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Tidak ada data.</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-800 flex justify-between items-center text-xs font-bold text-gray-600 dark:text-gray-400">
                    <span>GRAND TOTAL :</span>
                    <span class="text-purple-600 dark:text-purple-400 font-mono text-sm">{{ number_format($report['sum_total_employees']) }}</span>
                </div>
            </div>

        </div>
    </x-filament::section>

    {{-- Riwayat Sinkronisasi (Sync History Log Table) --}}
    <x-filament::section title="Riwayat Sinkronisasi (Sync History)" description="Catatan log eksekusi otomatis maupun manual dari cron Odoo Sync.">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50 text-xs uppercase font-semibold text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Waktu Sync</th>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">Batch ID</th>
                        <th class="px-4 py-3 text-center">Trigger</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">New</th>
                        <th class="px-4 py-3 text-center">Update</th>
                        <th class="px-4 py-3 text-center">Resign</th>
                        <th class="px-4 py-3 text-center">Total Emp</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 font-mono text-xs">
                    @forelse ($historyLogs as $log)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-sans">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 font-sans font-medium text-gray-900 dark:text-gray-100">
                                {{ $log->company->name ?? 'Semua Company' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $log->batch_id }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($log->trigger_type === 'cron')
                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 text-[10px] uppercase font-bold">Cron</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 text-[10px] uppercase font-bold">Manual</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($log->status === 'success')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 text-[10px] uppercase font-bold">Success</span>
                                @elseif ($log->status === 'partial')
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 text-[10px] uppercase font-bold">Partial</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300 text-[10px] uppercase font-bold">Failed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $log->new_count }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-blue-600 dark:text-blue-400">
                                {{ $log->update_count }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-rose-600 dark:text-rose-400">
                                {{ $log->resign_count }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($log->total_employee_count) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" wire:click="showLogDetail({{ $log->id }})" class="text-primary-600 hover:text-primary-500 underline font-sans text-xs">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-6 text-gray-400 italic">
                                Belum ada riwayat sinkronisasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Detail Modal --}}
    @if ($activeLogDetail)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Detail Log Sinkronisasi [{{ $activeLogDetail['batch_id'] }}]
                    </h3>
                    <button wire:click="closeLogDetail" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold block">NEW</span>
                        <span class="text-xl font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $activeLogDetail['new_count'] }}</span>
                    </div>
                    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800">
                        <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold block">UPDATE</span>
                        <span class="text-xl font-mono font-bold text-blue-700 dark:text-blue-300">{{ $activeLogDetail['update_count'] }}</span>
                    </div>
                    <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800">
                        <span class="text-[11px] text-rose-600 dark:text-rose-400 font-bold block">RESIGN</span>
                        <span class="text-xl font-mono font-bold text-rose-700 dark:text-rose-300">{{ $activeLogDetail['resign_count'] }}</span>
                    </div>
                    <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800">
                        <span class="text-[11px] text-purple-600 dark:text-purple-400 font-bold block">TOTAL EMP</span>
                        <span class="text-xl font-mono font-bold text-purple-700 dark:text-purple-300">{{ number_format($activeLogDetail['total_employee_count']) }}</span>
                    </div>
                </div>

                @if (!empty($activeLogDetail['details']['new_employees']))
                    <div class="space-y-1">
                        <h5 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase">Karyawan Baru Terdaftar:</h5>
                        <div class="max-h-32 overflow-y-auto bg-gray-50 dark:bg-gray-800/40 p-2.5 rounded-lg text-xs font-mono space-y-1">
                            @foreach ($activeLogDetail['details']['new_employees'] as $emp)
                                <div class="flex justify-between">
                                    <span>{{ $emp['name'] }} ({{ $emp['nik'] ?? '-' }})</span>
                                    <span class="text-gray-500">{{ $emp['position'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($activeLogDetail['details']['resigned_employees']))
                    <div class="space-y-1">
                        <h5 class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase">Karyawan Resign / Dinonaktifkan:</h5>
                        <div class="max-h-32 overflow-y-auto bg-gray-50 dark:bg-gray-800/40 p-2.5 rounded-lg text-xs font-mono space-y-1 text-rose-600 dark:text-rose-400">
                            @foreach ($activeLogDetail['details']['resigned_employees'] as $emp)
                                <div>{{ $emp['name'] }} ({{ $emp['nik'] ?? '-' }})</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($activeLogDetail['error_message']))
                    <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs rounded-lg">
                        <strong>Error:</strong> {{ $activeLogDetail['error_message'] }}
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <x-filament::button wire:click="closeLogDetail" color="gray" size="sm">
                        Tutup
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
