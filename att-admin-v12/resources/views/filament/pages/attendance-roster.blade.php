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
                                    <div class="font-medium mb-1
                                        @if($attendance->status === 'present') text-emerald-600 dark:text-emerald-400
                                        @elseif($attendance->status === 'absent') text-red-600 dark:text-red-400
                                        @elseif($attendance->status === 'late') text-amber-600 dark:text-amber-400
                                        @elseif($attendance->status === 'leave') text-blue-600 dark:text-blue-400
                                        @else text-gray-600 dark:text-gray-400 @endif
                                    ">
                                        {{ ucfirst($attendance->status) }}
                                    </div>
                                    <div class="text-xs text-gray-950 dark:text-white">
                                        In: {{ $attendance->checkin_at ? \Carbon\Carbon::parse($attendance->checkin_at)->timezone('Asia/Jakarta')->format('H:i') : '--:--' }}
                                    </div>
                                    <div class="text-xs text-gray-950 dark:text-white mt-0.5">
                                        Out: {{ $attendance->checkout_at ? \Carbon\Carbon::parse($attendance->checkout_at)->timezone('Asia/Jakarta')->format('H:i') : '--:--' }}
                                    </div>
                                @else
                                    <div class="text-gray-400 dark:text-gray-500 text-sm">No Data</div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
