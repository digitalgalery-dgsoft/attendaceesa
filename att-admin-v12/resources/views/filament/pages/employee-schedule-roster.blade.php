<x-filament-panels::page>
    <div class="mb-6">
        {{ $this->form }}
        <div class="mt-2 text-xs text-gray-500">
            * Menampilkan maksimal 31 hari
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10" style="overflow-x: auto; width: 100%;">
        <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/5" style="min-width: max-content;">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 sm:px-6 font-semibold text-gray-950 dark:text-white sticky left-0 bg-gray-50 dark:bg-gray-800 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_rgba(255,255,255,0.05)]" style="min-width: 250px;">Employee</th>
                    @for ($d = 1; $d <= $daysInPeriod; $d++)
                        @php
                            $date = $startDate->copy()->addDays($d - 1);
                        @endphp
                        <th class="px-4 py-3 sm:px-6 font-semibold text-gray-950 dark:text-white text-center" style="min-width: 140px; white-space: nowrap;">
                            {{ $date->format('M d, Y') }}<br>
                            <span class="font-normal text-gray-500 dark:text-gray-400">{{ $date->format('l') }}</span>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @foreach ($employees as $employee)
                    <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-white/5' : 'bg-white dark:bg-gray-900' }} hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        <td class="px-4 py-4 sm:px-6 sticky left-0 {{ $loop->even ? 'bg-gray-50 dark:bg-[#1a202c]' : 'bg-white dark:bg-gray-900' }} z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_rgba(255,255,255,0.05)]" style="min-width: 250px;">
                            <div class="font-medium text-gray-950 dark:text-white truncate">{{ $employee->full_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                {{ $employee->position?->name ?? 'N/A' }} . {{ $employee->branch?->name ?? 'N/A' }}
                            </div>
                        </td>
                        @for ($d = 1; $d <= $daysInPeriod; $d++)
                            @php
                                $dateStr = $startDate->copy()->addDays($d - 1)->toDateString();
                                $schedule = $schedules->get($employee->id)?->firstWhere('schedule_date', $dateStr);
                            @endphp
                            <td class="px-4 py-4 sm:px-6 align-top" style="min-width: 140px; white-space: nowrap;">
                                @if ($schedule && $schedule->schedule_type == 'workday')
                                    <div class="text-emerald-600 dark:text-emerald-400 font-medium mb-1">Planned</div>
                                    <div class="text-gray-950 dark:text-white text-sm">
                                        {{ $schedule->planned_start_at ? \Carbon\Carbon::parse($schedule->planned_start_at)->format('H:i') : '--:--' }} - {{ $schedule->planned_end_at ? \Carbon\Carbon::parse($schedule->planned_end_at)->format('H:i') : '--:--' }}
                                    </div>
                                    <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400 space-x-1">
                                        <x-heroicon-o-calendar style="width: 14px; height: 14px; flex-shrink: 0; display: inline-block;" />
                                        <span>{{ $schedule->shift?->code ?? 'N/A' }}</span>
                                    </div>
                                @else
                                    <div class="text-gray-400 dark:text-gray-500 text-sm">No Plan</div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
