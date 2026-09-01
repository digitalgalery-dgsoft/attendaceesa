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
    $hasActiveFilter = !empty($this->filters['principal_id']) || !empty($this->filters['branch_id']);
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        @if (method_exists($this, 'getFiltersSchema'))
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
                                    <svg style="width: 16px; height: 16px; color: #0284c7;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    <span style="font-size: 13px; font-weight: 700; color: #374151; letter-spacing: 0.05em; text-transform: uppercase;">Filter Grafik</span>
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
        @endif

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
</x-filament-widgets::widget>
