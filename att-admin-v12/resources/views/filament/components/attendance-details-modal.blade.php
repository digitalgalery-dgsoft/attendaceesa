<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Date</div>
            <div class="text-base text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</div>
        </div>
        <div>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</div>
            <div class="text-base font-bold uppercase
                @if($attendance?->status === 'present') text-emerald-600
                @elseif($attendance?->status === 'absent') text-red-600
                @elseif($attendance?->status === 'late') text-amber-600
                @else text-gray-900 dark:text-white @endif
            ">
                {{ $attendance ? $attendance->status : 'No Data' }}
            </div>
        </div>
        <div>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Scheduled Location</div>
            <div class="text-base text-gray-900 dark:text-white truncate" title="{{ $attendance?->employeeSchedule?->workLocation?->name }}">
                {{ $attendance?->employeeSchedule?->workLocation?->name ?? 'No Schedule' }}
            </div>
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Activity Logs</h3>
        
        @if (empty($logs) || count($logs) === 0)
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center text-gray-500 dark:text-gray-400">
                No activity logs recorded for this day.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($logs as $log)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 pr-4">
                                <div class="mb-2">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium uppercase
                                        @if(str_contains($log->log_type, 'in')) bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20
                                        @else bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20 @endif
                                    ">
                                        {{ str_replace('_', ' ', $log->log_type) }}
                                    </span>
                                    <span class="ml-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Jakarta')->format('H:i:s') }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex items-start">
                                        <x-filament::icon icon="heroicon-o-map-pin" class="w-5 h-5 mr-2 text-gray-400 flex-shrink-0" />
                                        <span>{{ $log->address_text ?? 'Location not recorded' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            @if ($log->photo_path)
                                <div class="flex-shrink-0">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($log->photo_path) }}" target="_blank" title="Click to view full image">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($log->photo_path) }}" alt="Activity Photo" style="width: 72px; height: 72px; object-fit: cover; display: block;" class="rounded-md border border-gray-200 dark:border-gray-700 hover:opacity-75 transition shadow-sm bg-gray-100">
                                    </a>
                                </div>
                            @endif
                        </div>

                        @if ($log->latitude && $log->longitude)
                            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                <iframe 
                                    width="100%" 
                                    height="200" 
                                    style="border:0" 
                                    loading="lazy" 
                                    allowfullscreen 
                                    src="https://maps.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}&z=15&output=embed">
                                </iframe>
                            </div>
                        @endif

                        @if ($log->note)
                            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 italic">
                                "{{ $log->note }}"
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
