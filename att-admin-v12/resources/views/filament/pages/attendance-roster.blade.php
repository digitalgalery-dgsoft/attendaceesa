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
                                $attendance = $attendances->get($employee->id)?->firstWhere('attendance_date', $dateStr);
                            @endphp
                            <td class="px-4 py-4 sm:px-6 align-top cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" style="min-width: 140px; white-space: nowrap;" wire:click="mountAction('viewDetails', { employee_id: {{ $employee->id }}, date: '{{ $dateStr }}' })">
                                @if ($attendance)
                                    <div class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-2
                                        @if($attendance->status === 'present') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400
                                        @elseif($attendance->status === 'absent') bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400
                                        @elseif($attendance->status === 'late') bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400
                                        @elseif($attendance->status === 'leave') bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-400 @endif
                                    ">
                                        {{ ucfirst($attendance->status) }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300">
                                        <x-heroicon-o-arrow-right-end-on-rectangle style="width: 14px; height: 14px; flex-shrink: 0;" class="text-emerald-500" />
                                        <span>In: <strong>{{ $attendance->checkin_at ? \Carbon\Carbon::parse($attendance->checkin_at)->timezone('Asia/Jakarta')->format('H:i') : '--:--' }}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 mt-1">
                                        <x-heroicon-o-arrow-left-start-on-rectangle style="width: 14px; height: 14px; flex-shrink: 0;" class="text-rose-500" />
                                        <span>Out: <strong>{{ $attendance->checkout_at ? \Carbon\Carbon::parse($attendance->checkout_at)->timezone('Asia/Jakarta')->format('H:i') : '--:--' }}</strong></span>
                                    </div>
                                @else
                                    <div class="text-gray-400 dark:text-gray-500 text-sm italic">No Data</div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
