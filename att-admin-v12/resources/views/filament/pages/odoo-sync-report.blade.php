<x-filament-panels::page>
    <style>
        .odoo-sync-grid-4col {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }
        .odoo-sync-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 1200px) {
            .odoo-sync-grid-4col {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .odoo-sync-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 640px) {
            .odoo-sync-grid-4col {
                grid-template-columns: 1fr !important;
            }
            .odoo-sync-kpi-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    @php
        $report = $this->getReportData();
        $batches = $this->getRecentBatches();
        $historyLogs = $this->getHistoryLogs();
    @endphp

    {{-- Filter & Control Header --}}
    <x-filament::section>
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px; flex: 1;">
                <div style="min-width: 280px; max-width: 420px; width: 100%;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block; margin-bottom: 4px;">Pilih Batch Sinkronisasi</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedBatchId" style="width: 100%; font-size: 13px;">
                            <option value="latest">-- Batch Terakhir (Default) --</option>
                            @foreach ($batches as $batchId => $label)
                                <option value="{{ $batchId }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if ($report['executed_at'])
                    <div style="padding: 8px 14px; border-radius: 8px; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.08); font-size: 12px;" class="dark:!bg-white/5 dark:!border-white/10">
                        <div><strong>Waktu Eksekusi:</strong> {{ $report['executed_at'] }}</div>
                        <div><strong>Tipe Trigger:</strong> <span style="font-weight: 700; text-transform: uppercase; color: #2563eb;">{{ $report['trigger_type'] ?? 'Cron' }}</span></div>
                    </div>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <x-filament::button wire:click="$refresh" color="gray" icon="heroicon-o-arrow-path" size="sm">
                    Refresh
                </x-filament::button>
                <x-filament::button tag="a" href="{{ route('filament.admin.pages.odoo-sync') }}" color="primary" icon="heroicon-o-cog-6-tooth" size="sm">
                    Kelola Odoo Sync
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Top Metric KPI Cards --}}
    <div class="odoo-sync-kpi-grid">
        {{-- Card 1: New (Green) --}}
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02)); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #059669; display: block; margin-bottom: 4px;">Total Employee New</span>
                    <span style="font-size: 28px; font-weight: 800; color: #047857; line-height: 1;">{{ number_format($report['sum_new']) }}</span>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; color: #059669;">
                    <x-filament::icon icon="heroicon-o-user-plus" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- Card 2: Update (Blue) --}}
        <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #2563eb; display: block; margin-bottom: 4px;">Total Employee Update</span>
                    <span style="font-size: 28px; font-weight: 800; color: #1d4ed8; line-height: 1;">{{ number_format($report['sum_update']) }}</span>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); display: flex; align-items: center; justify-content: center; color: #2563eb;">
                    <x-filament::icon icon="heroicon-o-arrow-path" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- Card 3: Resign (Rose/Red) --}}
        <div style="background: linear-gradient(135deg, rgba(244, 63, 94, 0.08), rgba(244, 63, 94, 0.02)); border: 1px solid rgba(244, 63, 94, 0.3); border-radius: 12px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #e11d48; display: block; margin-bottom: 4px;">Total Employee Resign</span>
                    <span style="font-size: 28px; font-weight: 800; color: #be123c; line-height: 1;">{{ number_format($report['sum_resign']) }}</span>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(244, 63, 94, 0.15); display: flex; align-items: center; justify-content: center; color: #e11d48;">
                    <x-filament::icon icon="heroicon-o-user-minus" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- Card 4: Total Employees (Purple) --}}
        <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(139, 92, 246, 0.02)); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #7c3aed; display: block; margin-bottom: 4px;">Total Seluruh Employee</span>
                    <span style="font-size: 28px; font-weight: 800; color: #6d28d9; line-height: 1;">{{ number_format($report['sum_total_employees']) }}</span>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(139, 92, 246, 0.15); display: flex; align-items: center; justify-content: center; color: #7c3aed;">
                    <x-filament::icon icon="heroicon-o-users" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Main 4-Column Layout (Matching User Mockup) --}}
    <x-filament::section>
        <div class="odoo-sync-grid-4col">
            
            {{-- Column 1: Data Employee New --}}
            <div style="background: var(--card-sub-bg, rgba(0,0,0,0.015)); border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;" class="dark:!bg-white/5 dark:!border-white/10">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; margin-bottom: 14px; border-bottom: 2px solid #10b981;">
                        <span style="font-size: 15px; font-weight: 700; color: #047857;" class="dark:!text-emerald-400">Data Employee New</span>
                        <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #d1fae5; color: #065f46;">{{ $report['sum_new'] }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @forelse ($report['new_data'] as $item)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.03);" class="dark:!bg-white/5">
                                <span style="font-size: 13px; font-weight: 600; color: #374151; word-break: break-word;" class="dark:!text-gray-200">
                                    {{ $item['name'] }} :
                                </span>
                                <span style="font-size: 14px; font-weight: 700; color: {{ $item['count'] > 0 ? '#059669' : '#9ca3af' }}; font-family: monospace; margin-left: 8px;">
                                    {{ $item['count'] }}
                                </span>
                            </div>
                        @empty
                            <p style="font-size: 12px; color: #9ca3af; font-style: italic;">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 700; color: #4b5563;" class="dark:!border-white/10 dark:!text-gray-400">
                    <span>TOTAL NEW :</span>
                    <span style="font-size: 16px; font-weight: 800; color: #059669; font-family: monospace;">{{ $report['sum_new'] }}</span>
                </div>
            </div>

            {{-- Column 2: Data Employee Update --}}
            <div style="background: var(--card-sub-bg, rgba(0,0,0,0.015)); border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;" class="dark:!bg-white/5 dark:!border-white/10">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; margin-bottom: 14px; border-bottom: 2px solid #3b82f6;">
                        <span style="font-size: 15px; font-weight: 700; color: #1d4ed8;" class="dark:!text-blue-400">Data Employee Update</span>
                        <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #dbeafe; color: #1e40af;">{{ $report['sum_update'] }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @forelse ($report['update_data'] as $item)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.03);" class="dark:!bg-white/5">
                                <span style="font-size: 13px; font-weight: 600; color: #374151; word-break: break-word;" class="dark:!text-gray-200">
                                    {{ $item['name'] }} :
                                </span>
                                <span style="font-size: 14px; font-weight: 700; color: {{ $item['count'] > 0 ? '#2563eb' : '#9ca3af' }}; font-family: monospace; margin-left: 8px;">
                                    {{ $item['count'] }}
                                </span>
                            </div>
                        @empty
                            <p style="font-size: 12px; color: #9ca3af; font-style: italic;">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 700; color: #4b5563;" class="dark:!border-white/10 dark:!text-gray-400">
                    <span>TOTAL UPDATE :</span>
                    <span style="font-size: 16px; font-weight: 800; color: #2563eb; font-family: monospace;">{{ $report['sum_update'] }}</span>
                </div>
            </div>

            {{-- Column 3: Data Employee Resign --}}
            <div style="background: var(--card-sub-bg, rgba(0,0,0,0.015)); border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;" class="dark:!bg-white/5 dark:!border-white/10">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; margin-bottom: 14px; border-bottom: 2px solid #f43f5e;">
                        <span style="font-size: 15px; font-weight: 700; color: #be123c;" class="dark:!text-rose-400">Data Employee Resign</span>
                        <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #ffe4e6; color: #9f1239;">{{ $report['sum_resign'] }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @forelse ($report['resign_data'] as $item)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.03);" class="dark:!bg-white/5">
                                <span style="font-size: 13px; font-weight: 600; color: #374151; word-break: break-word;" class="dark:!text-gray-200">
                                    {{ $item['name'] }} :
                                </span>
                                <span style="font-size: 14px; font-weight: 700; color: {{ $item['count'] > 0 ? '#e11d48' : '#9ca3af' }}; font-family: monospace; margin-left: 8px;">
                                    {{ $item['count'] }}
                                </span>
                            </div>
                        @empty
                            <p style="font-size: 12px; color: #9ca3af; font-style: italic;">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 700; color: #4b5563;" class="dark:!border-white/10 dark:!text-gray-400">
                    <span>TOTAL RESIGN :</span>
                    <span style="font-size: 16px; font-weight: 800; color: #e11d48; font-family: monospace;">{{ $report['sum_resign'] }}</span>
                </div>
            </div>

            {{-- Column 4: Total Seluruh Employee per Company --}}
            <div style="background: var(--card-sub-bg, rgba(0,0,0,0.015)); border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;" class="dark:!bg-white/5 dark:!border-white/10">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; margin-bottom: 14px; border-bottom: 2px solid #8b5cf6;">
                        <span style="font-size: 15px; font-weight: 700; color: #6d28d9;" class="dark:!text-purple-400">Total Seluruh Employee</span>
                        <span style="font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #ede9fe; color: #5b21b6;">{{ number_format($report['sum_total_employees']) }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @forelse ($report['total_employee_data'] as $item)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.03);" class="dark:!bg-white/5">
                                <span style="font-size: 13px; font-weight: 600; color: #374151; word-break: break-word;" class="dark:!text-gray-200">
                                    {{ $item['name'] }} :
                                </span>
                                <span style="font-size: 14px; font-weight: 700; color: #7c3aed; font-family: monospace; margin-left: 8px;">
                                    {{ number_format($item['count']) }}
                                </span>
                            </div>
                        @empty
                            <p style="font-size: 12px; color: #9ca3af; font-style: italic;">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 700; color: #4b5563;" class="dark:!border-white/10 dark:!text-gray-400">
                    <span>GRAND TOTAL :</span>
                    <span style="font-size: 16px; font-weight: 800; color: #7c3aed; font-family: monospace;">{{ number_format($report['sum_total_employees']) }}</span>
                </div>
            </div>

        </div>
    </x-filament::section>

    {{-- Tabel Riwayat Sinkronisasi (Sync History) --}}
    <x-filament::section title="Riwayat Sinkronisasi (Sync History)" description="Catatan log eksekusi otomatis maupun manual dari cron Odoo Sync.">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; background: rgba(0,0,0,0.02);" class="dark:!border-white/10 dark:!bg-white/5">
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280;">Waktu Sync</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280;">Company</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280;">Batch ID</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Trigger</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Status</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">New</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Update</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Resign</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Total Emp</th>
                        <th style="padding: 10px 14px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #6b7280; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historyLogs as $log)
                        <tr style="border-bottom: 1px solid #f3f4f6;" class="dark:!border-white/5">
                            <td style="padding: 10px 14px; color: #111827;" class="dark:!text-gray-200">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td style="padding: 10px 14px; font-weight: 600; color: #111827;" class="dark:!text-gray-100">
                                {{ $log->company->name ?? 'Semua Company' }}
                            </td>
                            <td style="padding: 10px 14px; color: #6b7280; font-family: monospace; font-size: 12px;">
                                {{ $log->batch_id }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if ($log->trigger_type === 'cron')
                                    <span style="padding: 2px 8px; border-radius: 12px; background: #dbeafe; color: #1e40af; font-size: 10px; font-weight: 700; text-transform: uppercase;">Cron</span>
                                @else
                                    <span style="padding: 2px 8px; border-radius: 12px; background: #fef3c7; color: #92400e; font-size: 10px; font-weight: 700; text-transform: uppercase;">Manual</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if ($log->status === 'success')
                                    <span style="padding: 2px 8px; border-radius: 12px; background: #d1fae5; color: #065f46; font-size: 10px; font-weight: 700; text-transform: uppercase;">Success</span>
                                @elseif ($log->status === 'partial')
                                    <span style="padding: 2px 8px; border-radius: 12px; background: #fef3c7; color: #92400e; font-size: 10px; font-weight: 700; text-transform: uppercase;">Partial</span>
                                @else
                                    <span style="padding: 2px 8px; border-radius: 12px; background: #ffe4e6; color: #9f1239; font-size: 10px; font-weight: 700; text-transform: uppercase;">Failed</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: #059669; font-family: monospace;">
                                {{ $log->new_count }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: #2563eb; font-family: monospace;">
                                {{ $log->update_count }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: #e11d48; font-family: monospace;">
                                {{ $log->resign_count }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: #7c3aed; font-family: monospace;">
                                {{ number_format($log->total_employee_count) }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <button type="button" wire:click="showLogDetail({{ $log->id }})" style="color: #4f46e5; text-decoration: underline; font-size: 12px; cursor: pointer; background: none; border: none;">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="padding: 24px; text-align: center; color: #9ca3af; font-style: italic;">
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
        <div style="position: fixed; inset: 0; z-index: 50; overflow-y: auto; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div style="background: var(--modal-bg, #ffffff); border-radius: 16px; max-width: 650px; width: 100%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); border: 1px solid #e5e7eb;" class="dark:!bg-gray-900 dark:!border-gray-800">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 16px;" class="dark:!border-gray-800">
                    <h3 style="font-size: 16px; font-weight: 700; color: #111827;" class="dark:!text-white">
                        Detail Log Sinkronisasi [{{ $activeLogDetail['batch_id'] }}]
                    </h3>
                    <button wire:click="closeLogDetail" style="color: #9ca3af; font-size: 24px; font-weight: 700; background: none; border: none; cursor: pointer;">&times;</button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; text-align: center; margin-bottom: 16px;">
                    <div style="padding: 10px; border-radius: 8px; background: #d1fae5; border: 1px solid #a7f3d0;">
                        <span style="font-size: 10px; color: #065f46; font-weight: 700; display: block;">NEW</span>
                        <span style="font-size: 18px; font-family: monospace; font-weight: 800; color: #047857;">{{ $activeLogDetail['new_count'] }}</span>
                    </div>
                    <div style="padding: 10px; border-radius: 8px; background: #dbeafe; border: 1px solid #bfdbfe;">
                        <span style="font-size: 10px; color: #1e40af; font-weight: 700; display: block;">UPDATE</span>
                        <span style="font-size: 18px; font-family: monospace; font-weight: 800; color: #1d4ed8;">{{ $activeLogDetail['update_count'] }}</span>
                    </div>
                    <div style="padding: 10px; border-radius: 8px; background: #ffe4e6; border: 1px solid #fecdd3;">
                        <span style="font-size: 10px; color: #9f1239; font-weight: 700; display: block;">RESIGN</span>
                        <span style="font-size: 18px; font-family: monospace; font-weight: 800; color: #be123c;">{{ $activeLogDetail['resign_count'] }}</span>
                    </div>
                    <div style="padding: 10px; border-radius: 8px; background: #ede9fe; border: 1px solid #ddd6fe;">
                        <span style="font-size: 10px; color: #5b21b6; font-weight: 700; display: block;">TOTAL EMP</span>
                        <span style="font-size: 18px; font-family: monospace; font-weight: 800; color: #6d28d9;">{{ number_format($activeLogDetail['total_employee_count']) }}</span>
                    </div>
                </div>

                @if (!empty($activeLogDetail['details']['new_employees']))
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; display: block; margin-bottom: 4px;">Karyawan Baru Terdaftar:</span>
                        <div style="max-height: 120px; overflow-y: auto; background: rgba(0,0,0,0.03); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-family: monospace;" class="dark:!bg-white/5">
                            @foreach ($activeLogDetail['details']['new_employees'] as $emp)
                                <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                                    <span>{{ $emp['name'] }} ({{ $emp['nik'] ?? '-' }})</span>
                                    <span style="color: #6b7280;">{{ $emp['position'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($activeLogDetail['details']['resigned_employees']))
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 700; color: #e11d48; text-transform: uppercase; display: block; margin-bottom: 4px;">Karyawan Resign / Dinonaktifkan:</span>
                        <div style="max-height: 120px; overflow-y: auto; background: rgba(0,0,0,0.03); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-family: monospace; color: #be123c;" class="dark:!bg-white/5">
                            @foreach ($activeLogDetail['details']['resigned_employees'] as $emp)
                                <div style="padding: 2px 0;">{{ $emp['name'] }} ({{ $emp['nik'] ?? '-' }})</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($activeLogDetail['error_message']))
                    <div style="padding: 10px; background: #ffe4e6; border: 1px solid #fecdd3; color: #9f1239; font-size: 12px; border-radius: 8px; margin-bottom: 12px;">
                        <strong>Error:</strong> {{ $activeLogDetail['error_message'] }}
                    </div>
                @endif

                <div style="display: flex; justify-content: flex-end; padding-top: 8px;">
                    <x-filament::button wire:click="closeLogDetail" color="gray" size="sm">
                        Tutup
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
