@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();
    $maxHeight = $this->getMaxHeight();
    $hasMaxHeight = filled($maxHeight) && $maxHeight !== '100%';
    $hasActiveFilter = (!empty($this->filters['time_range']) && $this->filters['time_range'] !== '12h')
        || !empty($this->filters['principal_id']) 
        || !empty($this->filters['branch_id']);
    $stats = $this->getSummaryStats();
@endphp

<x-filament-widgets::widget class="fi-wi-chart fi-wi-active-employees-hourly">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        <x-slot name="afterHeader">
            <div class="flex items-center gap-2">
                <x-filament::dropdown
                    placement="bottom-end"
                    shift
                    width="md"
                    class="fi-wi-chart-filter"
                >
                    <x-slot name="trigger">
                        {{ $this->getFiltersTriggerAction() }}
                    </x-slot>

                    <div style="padding: 22px; width: 340px; max-width: 90vw;">
                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 12px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: #0f52ba;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                <span style="font-size: 13px; font-weight: 700; color: #1e293b; letter-spacing: 0.04em; text-transform: uppercase;">Filter Waktu & Area</span>
                            </div>
                            @if($hasActiveFilter)
                                <button 
                                    type="button" 
                                    wire:click="resetFiltersForm" 
                                    style="font-size: 12px; font-weight: 600; color: #ef4444; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                >
                                    Reset Filter
                                </button>
                            @endif
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            {{ $this->getFiltersSchema() }}
                        </div>
                    </div>
                </x-filament::dropdown>
            </div>
        </x-slot>

        <!-- Status & Sync Badge Banner -->
        <div class="hourly-notice-banner">
            <div class="hourly-notice-content">
                <svg class="hourly-notice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>Sinkronisasi Odoo Otomatis • Update Terjadwal Tiap 30 Menit • Terakhir: <strong>{{ $stats['latestSyncTime'] ?? 'Hari Ini' }}</strong></span>
            </div>
            <span class="hourly-notice-badge">
                Odoo Sync ERP
            </span>
        </div>

        <!-- Modern Top Stats KPI Bar -->
        <div class="active-hourly-stats-bar">
            <!-- Stat 1: Total Employee Aktif -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(15, 82, 186, 0.08); color: #0F52BA;">
                    <span class="live-pulse-dot"></span>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Total Employee Aktif</span>
                    <div class="hourly-stat-value" style="color: #0F52BA;">
                        {{ number_format($stats['totalActive']) }}
                        <span class="hourly-stat-unit">Karyawan</span>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Total Resign / Non-Aktif -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(100, 116, 139, 0.1); color: #475569;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Resign / Non-Aktif</span>
                    <div class="hourly-stat-value" style="color: #475569;">
                        {{ number_format($stats['totalInactive']) }}
                        <span class="hourly-stat-unit">Orang</span>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Penambahan Baru (+) -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Karyawan Baru (+)</span>
                    <div class="hourly-stat-value" style="color: #059669;">
                        +{{ number_format($stats['totalNew']) }}
                        <span class="hourly-stat-unit">Orang</span>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Mutasi Resign (-) -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Mutasi Resign (-)</span>
                    <div class="hourly-stat-value" style="color: #dc2626;">
                        -{{ number_format($stats['totalResigned']) }}
                        <span class="hourly-stat-unit">Orang</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Canvas Container -->
        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($type),
                        })"
                {{
                    (new ComponentAttributeBag)
                        ->color(ChartWidgetComponent::class, $color)
                        ->class([
                            'fi-wi-chart-canvas-ctn',
                            'fi-wi-chart-canvas-ctn-no-aspect-ratio' => $hasMaxHeight,
                        ])
                        ->style([
                            'max-height: ' . $maxHeight => $hasMaxHeight,
                        ])
                }}
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight)
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>
                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                ></span>
                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                ></span>
                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>
                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />

    <style>
        .hourly-notice-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: rgba(15, 82, 186, 0.06);
            border: 1px solid rgba(15, 82, 186, 0.2);
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .dark .hourly-notice-banner {
            background: rgba(15, 82, 186, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .hourly-notice-content {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0F52BA;
            line-height: 1.4;
        }

        .dark .hourly-notice-content {
            color: #93c5fd;
        }

        .hourly-notice-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            color: #0F52BA;
        }

        .dark .hourly-notice-icon {
            color: #60a5fa;
        }

        .hourly-notice-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background: #dbeafe;
            color: #1e40af;
            white-space: nowrap;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .dark .hourly-notice-badge {
            background: rgba(30, 58, 138, 0.8);
            color: #bfdbfe;
        }

        .active-hourly-stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        @media (max-width: 1024px) {
            .active-hourly-stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .active-hourly-stats-bar {
                grid-template-columns: 1fr;
            }
        }

        .hourly-stat-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .dark .hourly-stat-box {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: none;
        }

        .hourly-stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .hourly-stat-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
        }

        .live-pulse-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #10B981;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: activeLivePulse 1.8s infinite cubic-bezier(0.66, 0, 0, 1);
        }

        @keyframes activeLivePulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .hourly-stat-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .hourly-stat-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .hourly-stat-title {
            color: #94a3b8;
        }

        .hourly-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            display: flex;
            align-items: baseline;
            gap: 0.35rem;
        }

        .hourly-stat-unit {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
        }
    </style>
</x-filament-widgets::widget>
