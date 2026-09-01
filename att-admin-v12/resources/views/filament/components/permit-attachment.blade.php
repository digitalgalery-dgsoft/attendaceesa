@php
    $record = $getRecord();
    $path = $record?->attachment_path;
    $cleanPath = $path ? ltrim(str_replace(['public/', 'storage/'], '', $path), '/') : null;
    $streamUrl = $record ? url('/attachment-stream/' . $record->id) : null;
    $publicUrl = $cleanPath ? (str_starts_with($cleanPath, 'http') ? $cleanPath : url('storage/' . $cleanPath)) : null;
    $isPdf = $cleanPath && str_ends_with(strtolower($cleanPath), '.pdf');
@endphp

@if ($path)
    <div class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700">
        @if ($isPdf)
            <div class="flex flex-col items-center gap-3 py-6">
                <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Dokumen Lampiran (PDF)</span>
                <a href="{{ $streamUrl ?: $publicUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-primary-600 rounded-lg hover:bg-primary-500 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Buka / Unduh Dokumen PDF
                </a>
            </div>
        @else
            <div class="w-full flex flex-col items-center">
                <a href="{{ $streamUrl ?: $publicUrl }}" target="_blank" title="Klik untuk membuka ukuran penuh" class="group relative block overflow-hidden rounded-lg max-h-[500px] border border-gray-100 dark:border-gray-700">
                    <img 
                        src="{{ $streamUrl ?: $publicUrl }}" 
                        alt="Lampiran Permit" 
                        class="w-full max-h-[480px] object-contain rounded-lg shadow-sm group-hover:scale-[1.01] transition-transform duration-200"
                        onerror="if (this.src !== '{{ $publicUrl }}') { this.src = '{{ $publicUrl }}'; }"
                    />
                </a>
                <div class="mt-3 flex items-center gap-4 text-xs">
                    <a href="{{ $streamUrl ?: $publicUrl }}" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1 font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Buka Foto Ukuran Penuh
                    </a>
                </div>
            </div>
        @endif
    </div>
@else
    <p class="text-sm text-gray-500 italic">Tidak ada lampiran foto/dokumen untuk permit ini.</p>
@endif
