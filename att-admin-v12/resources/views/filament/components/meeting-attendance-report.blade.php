@php
    $participants = $meeting->participants ?? collect();
    $attendances = ($meeting->attendances ?? collect())->keyBy('employee_id');

    $completedCount = 0;
    $inMeetingCount = 0;
    $notAttendedCount = 0;

    foreach ($participants as $p) {
        $att = $attendances->get($p->employee_id);
        if ($att && $att->status === 'completed') {
            $completedCount++;
        } elseif ($att && $att->status === 'in_meeting') {
            $inMeetingCount++;
        } else {
            $notAttendedCount++;
        }
    }
@endphp

<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Peserta</div>
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{ $participants->count() }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Hadir / Selesai</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $completedCount }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Sedang Rapat</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $inMeetingCount }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Belum Hadir</div>
            <div class="text-2xl font-bold text-gray-500 dark:text-gray-400 mt-1">{{ $notAttendedCount }}</div>
        </div>
    </div>

    <!-- Participant Table -->
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Meet-In</th>
                    <th class="px-4 py-3">Meet-Out</th>
                    <th class="px-4 py-3">Durasi</th>
                    <th class="px-4 py-3">Catatan / Notulensi</th>
                    <th class="px-4 py-3 text-center">Foto Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($participants as $p)
                    @php
                        $emp = $p->employee;
                        $att = $attendances->get($p->employee_id);
                        $photo = $att?->meet_out_photo ?? $att?->meet_in_photo;
                        $durationFormatted = '-';
                        if ($att && $att->duration_seconds) {
                            $hours = floor($att->duration_seconds / 3600);
                            $mins = floor(($att->duration_seconds % 3600) / 60);
                            $secs = $att->duration_seconds % 60;
                            $durationFormatted = ($hours > 0 ? sprintf('%02d:%02d:%02d', $hours, $mins, $secs) : sprintf('%02d:%02d', $mins, $secs));
                        } elseif ($att && $att->status === 'in_meeting') {
                            $durationFormatted = 'Berlangsung';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $emp?->full_name ?? ($emp?->name ?? 'Karyawan') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">NIK: {{ $emp?->employee_no ?? '-' }} · {{ is_object($emp?->position) ? $emp->position->name : ($emp?->position ?? '-') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($att && $att->status === 'completed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                    Hadir (Selesai)
                                </span>
                            @elseif ($att && $att->status === 'in_meeting')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                    Sedang Rapat
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    Belum Hadir
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ $att?->meet_in_at ? $att->meet_in_at->format('H:i:s') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ $att?->meet_out_at ? $att->meet_out_at->format('H:i:s') : ($att && $att->status === 'in_meeting' ? 'Berjalan' : '-') }}
                        </td>
                        <td class="px-4 py-3 text-xs font-bold text-primary-600 dark:text-primary-400">
                            {{ $durationFormatted }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 max-w-xs">
                            {{ $att?->report_notes ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($photo)
                                <a href="{{ asset('storage/' . $photo) }}" target="_blank" class="inline-block">
                                    <img src="{{ asset('storage/' . $photo) }}" alt="Foto Bukti" class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:opacity-80 transition" />
                                </a>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                            Belum ada peserta yang terdaftar pada meeting ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
