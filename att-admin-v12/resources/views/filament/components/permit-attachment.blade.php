@php
    $record = $getRecord();
    $path = $record?->attachment_path;
    $cleanPath = $path ? ltrim(str_replace(['public/', 'storage/'], '', $path), '/') : null;
    $streamUrl = $record ? url('/attachment-stream/' . $record->id) : null;
    $publicUrl = $cleanPath ? (str_starts_with($cleanPath, 'http') ? $cleanPath : url('storage/' . $cleanPath)) : null;
    $targetUrl = $streamUrl ?: $publicUrl;
    $isPdf = $cleanPath && str_ends_with(strtolower($cleanPath), '.pdf');
@endphp

@if ($path)
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; background: rgba(0, 0, 0, 0.02); border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.08);">
        @if ($isPdf)
            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px 12px;">
                <svg style="width: 54px; height: 54px; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span style="font-size: 13px; font-weight: 600;">Dokumen Lampiran (PDF)</span>
                <a href="{{ $targetUrl }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; color: #ffffff; background: #0284c7; border-radius: 8px; text-decoration: none;">
                    Buka / Unduh Dokumen PDF
                </a>
            </div>
        @else
            <div style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                <a href="{{ $targetUrl }}" target="_blank" title="Klik untuk memperbesar foto" style="display: block; width: 100%; text-align: center; max-height: 450px; overflow: hidden; border-radius: 8px;">
                    <img 
                        src="{{ $targetUrl }}" 
                        alt="Lampiran Permit" 
                        style="max-width: 100%; max-height: 420px; width: auto; height: auto; object-fit: contain; border-radius: 8px; border: 1px solid rgba(0, 0, 0, 0.08); margin: 0 auto;"
                        onerror="if (this.src !== '{{ $publicUrl }}') { this.src = '{{ $publicUrl }}'; }"
                    />
                </a>
                <div style="margin-top: 10px; text-align: center;">
                    <a href="{{ $targetUrl }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #0284c7; text-decoration: none; padding: 4px 10px; border-radius: 6px; background: rgba(2, 132, 199, 0.08);">
                        <svg style="width: 13px; height: 13px; display: inline-block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Buka Foto Ukuran Penuh
                    </a>
                </div>
            </div>
        @endif
    </div>
@else
    <p style="font-size: 13px; color: #6b7280; font-style: italic;">Tidak ada lampiran foto/dokumen untuk permit ini.</p>
@endif
