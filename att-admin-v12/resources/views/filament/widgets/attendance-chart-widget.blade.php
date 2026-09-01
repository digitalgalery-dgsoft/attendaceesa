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
    $principalOptions = $this->getPrincipalOptions();
    $branchOptions = $this->getBranchOptions();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        <x-slot name="afterHeader">
            <div class="flex flex-row flex-wrap items-center justify-end gap-2.5">
                {{-- Dropdown Searchable Filter: Prinsiple --}}
                <div 
                    x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('principal_id'),
                        options: @js($principalOptions),
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            const q = this.search.toLowerCase();
                            const res = {};
                            for (const [k, v] of Object.entries(this.options)) {
                                if (v.toLowerCase().includes(q)) {
                                    res[k] = v;
                                }
                            }
                            return res;
                        },
                        get selectedLabel() {
                            return this.options[this.selected] || 'Semua Prinsiple';
                        }
                    }"
                    class="relative"
                    @click.outside="open = false; search = ''"
                >
                    <button 
                        type="button" 
                        @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 text-xs font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/60 transition min-w-[160px] max-w-[220px]"
                    >
                        <span class="inline-flex items-center gap-1.5 truncate">
                            <svg class="w-3.5 h-3.5 text-primary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="truncate" x-text="selectedLabel"></span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 13px; height: 13px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-1.5 w-64 p-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 text-xs"
                        style="display: none;"
                    >
                        <div class="relative mb-2">
                            <input 
                                x-ref="searchInput"
                                x-model="search" 
                                type="text" 
                                placeholder="Cari prinsiple..." 
                                class="w-full pl-7 pr-2.5 py-1 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                            <template x-for="(label, key) in filteredOptions" :key="key">
                                <button 
                                    type="button" 
                                    @click="selected = key; open = false; search = ''"
                                    :class="selected == key ? 'bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between transition"
                                >
                                    <span class="truncate" x-text="label"></span>
                                    <svg x-show="selected == key" class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 13px; height: 13px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </template>
                            <div x-show="Object.keys(filteredOptions).length === 0" class="py-2 text-center text-gray-400 italic">
                                Tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Searchable Filter: Area / Cabang --}}
                <div 
                    x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('branch_id'),
                        options: @js($branchOptions),
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            const q = this.search.toLowerCase();
                            const res = {};
                            for (const [k, v] of Object.entries(this.options)) {
                                if (v.toLowerCase().includes(q)) {
                                    res[k] = v;
                                }
                            }
                            return res;
                        },
                        get selectedLabel() {
                            return this.options[this.selected] || 'Semua Area';
                        }
                    }"
                    class="relative"
                    @click.outside="open = false; search = ''"
                >
                    <button 
                        type="button" 
                        @click="open = !open; if(open) $nextTick(() => $refs.searchInputArea.focus())"
                        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 text-xs font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/60 transition min-w-[140px] max-w-[200px]"
                    >
                        <span class="inline-flex items-center gap-1.5 truncate">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="truncate" x-text="selectedLabel"></span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 13px; height: 13px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-1.5 w-60 p-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 text-xs"
                        style="display: none;"
                    >
                        <div class="relative mb-2">
                            <input 
                                x-ref="searchInputArea"
                                x-model="search" 
                                type="text" 
                                placeholder="Cari area/cabang..." 
                                class="w-full pl-7 pr-2.5 py-1 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-0.5 custom-scrollbar">
                            <template x-for="(label, key) in filteredOptions" :key="key">
                                <button 
                                    type="button" 
                                    @click="selected = key; open = false; search = ''"
                                    :class="selected == key ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between transition"
                                >
                                    <span class="truncate" x-text="label"></span>
                                    <svg x-show="selected == key" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 13px; height: 13px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </template>
                            <div x-show="Object.keys(filteredOptions).length === 0" class="py-2 text-center text-gray-400 italic">
                                Tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reset Filter Button (hanya muncul jika salah satu filter aktif) --}}
                @if(!empty($this->principal_id) || !empty($this->branch_id))
                    <button 
                        type="button"
                        wire:click="$set('principal_id', ''); $set('branch_id', '');"
                        title="Reset Filter"
                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 15px; height: 15px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </x-slot>

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
</x-filament-widgets::widget>
