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

        <!-- Modern Top Stats KPI Bar -->
        <div class="active-hourly-stats-bar">
            <!-- Stat 1: Sedang Aktif Saat Ini -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(15, 82, 186, 0.08); color: #0F52BA;">
                    <span class="live-pulse-dot"></span>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Aktif Saat Ini</span>
                    <div class="hourly-stat-value" style="color: #0F52BA;">
                        {{ number_format($stats['currentActive']) }}
                        <span class="hourly-stat-unit">Karyawan</span>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Puncak Jam Kerja (Peak) -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: #d97706;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Puncak Jam Kerja (Peak)</span>
                    <div class="hourly-stat-value" style="color: #d97706;">
                        {{ number_format($stats['peakActive']) }}
                        <span class="hourly-stat-unit">({{ $stats['peakHour'] }} WIB)</span>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Check-in Baru -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Total Check-in Baru</span>
                    <div class="hourly-stat-value" style="color: #059669;">
                        +{{ number_format($stats['totalCheckins']) }}
                        <span class="hourly-stat-unit">Orang</span>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Check-out Selesai -->
            <div class="hourly-stat-box">
                <div class="hourly-stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="hourly-stat-details">
                    <span class="hourly-stat-title">Total Check-out</span>
                    <div class="hourly-stat-value" style="color: #dc2626;">
                        -{{ number_format($stats['totalCheckouts']) }}
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
                }}
                style="margin-top: 0.5rem;"
            >
                <canvas
                    x-ref="canvas"
                    @style([
                        'width: 100%',
                        'height: 100%; max-height: 100%' => ! $hasMaxHeight,
                        ('max-height: ' . e($maxHeight)) => $hasMaxHeight,
                    ])
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
