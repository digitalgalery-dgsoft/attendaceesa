@extends('portal.layout')

@section('title', 'Detail Laporan ' . $submission->submission_code . ' - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Detail Dokumen Laporan')
@section('breadcrumb_active', $submission->submission_code)

@push('styles')
<style>
    .report-view-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        font-family: 'Outfit', sans-serif;
    }

    /* FLASH ALERT */
    .alert-banner {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        animation: fadeIn 0.3s ease;
    }
    .alert-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .alert-danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    /* BANNER HEADER CARD */
    .report-banner-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
    }

    .banner-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .banner-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(15, 82, 186, 0.12) 0%, rgba(37, 99, 235, 0.08) 100%);
        color: #0F52BA;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
        border: 1px solid rgba(15, 82, 186, 0.2);
    }

    .banner-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.3px;
        line-height: 1.25;
        margin-bottom: 4px;
    }

    .banner-subtitle {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.85rem;
        color: var(--text-muted);
        flex-wrap: wrap;
    }

    .code-badge {
        font-family: monospace;
        font-weight: 700;
        background: rgba(15, 82, 186, 0.08);
        color: #0F52BA;
        padding: 2px 8px;
        border-radius: 6px;
        border: 1px solid rgba(15, 82, 186, 0.2);
        font-size: 0.82rem;
    }

    .banner-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 700;
        border: 1px solid;
        letter-spacing: 0.2px;
    }

    /* APPROVAL ACTION BUTTONS */
    .btn-action-approve {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #16a34a;
        color: #ffffff;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-approve:hover {
        background: #15803d;
        transform: translateY(-1px);
    }

    .btn-action-reject {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #dc2626;
        color: #ffffff;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-reject:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .btn-portal-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--text-heading);
        border: 1px solid var(--border-color);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-portal-back:hover {
        background: #e2e8f0;
    }

    /* 2-COLUMN OVERVIEW GRID */
    .overview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 900px) {
        .overview-grid {
            grid-template-columns: 1fr;
        }
    }

    .overview-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .overview-card-title {
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.88rem;
    }
    .info-label {
        color: var(--text-muted);
        font-weight: 600;
        min-width: 130px;
        font-size: 0.82rem;
    }
    .info-value {
        color: var(--text-heading);
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    /* SPLIT CONTENT GRID (FORM DATA + PHOTO GALLERY) */
    .content-split-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 1.5rem;
        align-items: start;
    }
    .content-split-grid.no-media {
        grid-template-columns: 1fr;
    }
    @media (max-width: 992px) {
        .content-split-grid {
            grid-template-columns: 1fr;
        }
    }

    .panel-container {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .panel-header {
        padding: 1.15rem 1.35rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .panel-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .panel-count-badge {
        font-size: 0.74rem;
        font-weight: 700;
        background: rgba(15, 82, 186, 0.08);
        color: #0F52BA;
        padding: 3px 10px;
        border-radius: 999px;
    }

    /* PARAMETER TABLE */
    .param-table {
        width: 100%;
        border-collapse: collapse;
    }
    .param-table tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }
    .param-table tr:last-child {
        border-bottom: none;
    }
    .param-table tr:hover {
        background: #f8fafc;
    }
    .param-table td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
    }

    .param-num-col {
        width: 44px;
        text-align: center;
        padding-right: 0 !important;
    }
    .param-num-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 800;
        border: 1px solid #e2e8f0;
    }

    .param-label-col {
        padding-left: 0.85rem !important;
    }
    .param-label-text {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.35;
    }

    .param-val-col {
        text-align: right;
    }
    .val-currency {
        font-family: monospace;
        font-size: 0.95rem;
        font-weight: 800;
        color: #15803d;
        background: #dcfce7;
        padding: 4px 10px;
        border-radius: 8px;
        border: 1px solid #86efac;
        display: inline-block;
    }
    .val-number {
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text-heading);
        background: #f1f5f9;
        padding: 3px 10px;
        border-radius: 6px;
        display: inline-block;
    }
    .val-text {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.4;
        word-break: break-word;
    }
    .val-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: flex-end;
    }
    .val-chip {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-heading);
    }
    .val-empty {
        color: #cbd5e1;
        font-style: italic;
    }

    /* MEDIA GALLERY */
    .media-gallery-grid {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .media-item-card {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .media-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .media-badge-tag {
        font-size: 0.74rem;
        font-weight: 700;
        color: #0F52BA;
        background: rgba(15, 82, 186, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .media-field-title {
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-heading);
        text-align: right;
        flex: 1;
    }

    .media-photo-frame {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .media-photo-frame img {
        width: 100%;
        max-height: 360px;
        object-fit: contain;
        transition: transform 0.2s ease;
    }
    .media-photo-frame img:hover {
        transform: scale(1.03);
    }

    .media-footer-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    .media-full-btn {
        font-size: 0.78rem;
        font-weight: 700;
        color: #0F52BA;
        background: none;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 6px;
        transition: background 0.15s ease;
    }
    .media-full-btn:hover {
        background: rgba(15, 82, 186, 0.08);
        text-decoration: underline;
    }

    /* LIGHTBOX MODAL */
    .lightbox-backdrop {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 1.5rem;
    }
    .lightbox-content-box {
        position: relative;
        max-width: 92vw;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: zoomIn 0.2s ease;
    }
    @keyframes zoomIn {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .lightbox-header {
        padding: 1rem 1.35rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .lightbox-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lightbox-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .lightbox-action-btn {
        background: #f1f5f9;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-heading);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.15s ease;
    }
    .lightbox-action-btn:hover {
        background: #e2e8f0;
    }
    .lightbox-close-btn {
        background: none;
        border: none;
        font-size: 1.4rem;
        color: var(--text-muted);
        cursor: pointer;
        line-height: 1;
        padding: 0 4px;
        transition: color 0.15s ease;
    }
    .lightbox-close-btn:hover {
        color: #ef4444;
    }
    .lightbox-image-wrap {
        padding: 1rem;
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        max-height: calc(92vh - 70px);
    }
    .lightbox-image-wrap img {
        max-width: 100%;
        max-height: 75vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* REJECT MODAL STYLING */
    .custom-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .custom-modal-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.75rem;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        animation: scaleUp 0.2s ease;
    }
    @keyframes scaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
@endpush

@section('content')
    @php
        $status = $submission->status ?? 'pending';
        $statusConfig = match ($status) {
            'approved', 'verified' => [
                'label' => 'Terverifikasi (Valid)',
                'bg' => '#dcfce7',
                'color' => '#15803d',
                'border' => '#86efac',
                'icon' => 'fa-circle-check',
            ],
            'rejected' => [
                'label' => 'Ditolak (Tidak Sesuai)',
                'bg' => '#fee2e2',
                'color' => '#b91c1c',
                'border' => '#fca5a5',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => 'Menunggu Verifikasi',
                'bg' => '#fef3c7',
                'color' => '#b45309',
                'border' => '#fde68a',
                'icon' => 'fa-clock',
            ],
        };

        $employee = $submission->employee;
        $workLocation = $submission->workLocation ?? $submission->itineraryItem;
        $storeName = $submission->workLocation?->name ?? $submission->itineraryItem?->destination ?? $submission->store_name ?? 'Kunjungan Toko';
        $coordinates = ($submission->latitude && $submission->longitude) ? "{$submission->latitude}, {$submission->longitude}" : null;
        $mapsUrl = $coordinates ? "https://www.google.com/maps?q={$submission->latitude},{$submission->longitude}" : null;

        // Separate text inputs and photo/media attachments to prevent tall empty grid cards
        $textValues = $submission->values->filter(function($val) {
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);
            return !$isMedia;
        });

        // Collect all individual media items (including multi-photo JSON array)
        $mediaItems = [];
        foreach ($submission->values as $val) {
            $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);

            if (!$isMedia) continue;

            $rawPaths = [];
            if (is_array($val->value_json) && !empty($val->value_json)) {
                $rawPaths = $val->value_json;
            } elseif (!empty($val->media_url)) {
                $rawPaths = [$val->media_url];
            } elseif (!empty($val->file_path)) {
                $rawPaths = [$val->file_path];
            } elseif (!empty($val->value_text) && (str_contains($val->value_text, 'reports/') || str_contains($val->value_text, 'storage/'))) {
                $rawPaths = array_map('trim', explode(',', $val->value_text));
            }

            $foundUrls = [];
            foreach ($rawPaths as $p) {
                if (empty($p) || !is_string($p)) continue;
                $clean = trim($p);
                
                // Abaikan jika berupa path lokal perangkat android
                if (str_starts_with($clean, '/data/user/') || str_starts_with($clean, 'data/user/') || str_contains($clean, 'cache/wm_')) {
                    continue;
                }

                // If it is a full URL
                if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
                    $clean = str_replace('/storage/storage/', '/storage/', $clean);
                    $url = $clean;
                } else {
                    if (str_starts_with($clean, 'storage/')) {
                        $clean = substr($clean, 8);
                    } elseif (str_starts_with($clean, '/storage/')) {
                        $clean = substr($clean, 9);
                    }
                    $url = asset('storage/' . ltrim($clean, '/'));
                }

                $foundUrls[] = [
                    'label' => $fieldLabel,
                    'url' => $url,
                    'path' => $clean,
                    'field_type' => $val->field_type,
                ];
            }

            // Fallback: Jika path di database rusak/lokal tapi file ada di disk server
            if (empty($foundUrls)) {
                $subId = $submission->id;
                $fieldId = $val->report_form_field_id;
                $pattern = "reports/*/report_{$subId}_{$fieldId}_*.jpg";
                $matches = glob(storage_path("app/public/{$pattern}"));
                if (empty($matches)) {
                    $pattern2 = "reports/*/report_{$subId}_*.jpg";
                    $matches = glob(storage_path("app/public/{$pattern2}"));
                }
                if (!empty($matches)) {
                    foreach ($matches as $match) {
                        $rel = str_replace(storage_path('app/public/'), '', $match);
                        $rel = str_replace('\\', '/', $rel);
                        $foundUrls[] = [
                            'label' => $fieldLabel,
                            'url' => asset('storage/' . ltrim($rel, '/')),
                            'path' => $rel,
                            'field_type' => $val->field_type,
                        ];
                    }
                }
            }

            $mediaItems = array_merge($mediaItems, $foundUrls);
        }
        $mediaValues = collect($mediaItems);
    @endphp

    <div class="report-view-wrapper">
        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert-banner alert-success">
                <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-banner alert-danger">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem;"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- BANNER HEADER CARD --}}
        <div class="report-banner-card">
            <div class="banner-left">
                <div class="banner-icon-box">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <div class="banner-title">
                        {{ $template->title }}
                    </div>
                    <div class="banner-subtitle">
                        <span>No. Laporan:</span>
                        <span class="code-badge">{{ $submission->submission_code }}</span>
                        <span>&bull;</span>
                        <span>Disubmit pada: <strong>{{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="banner-actions">
                {{-- STATUS BADGE --}}
                <div class="status-badge" style="background-color: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }}; border-color: {{ $statusConfig['border'] }};">
                    <i class="fa-solid {{ $statusConfig['icon'] }}"></i>
                    <span>{{ $statusConfig['label'] }}</span>
                </div>

                {{-- ACTION: APPROVE --}}
                @if(in_array($status, ['pending', 'submitted', 'rejected']))
                    <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $submission->id, 'p' => $tenantPrincipal->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (verifikasi valid) laporan ini?');">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn-action-approve">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Setujui Laporan</span>
                        </button>
                    </form>
                @endif

                {{-- ACTION: REJECT --}}
                @if(in_array($status, ['pending', 'submitted', 'approved', 'verified']))
                    <button type="button" class="btn-action-reject" onclick="openRejectModal()">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Tolak Laporan</span>
                    </button>
                @endif

                {{-- BACK BUTTON --}}
                <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" class="btn-portal-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        {{-- 2-COLUMN OVERVIEW GRID --}}
        <div class="overview-grid">
            {{-- CARD 1: INFORMASI PROMOTOR & OUTLET --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <i class="fa-solid fa-user-tie" style="color: #0F52BA;"></i>
                    <span>Informasi Pelapor & Toko</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Nama Promotor / SPG</span>
                    <span class="info-value">
                        {{ $employee?->full_name ?? $employee?->name ?? '-' }}
                        @if($employee?->nik)
                            <span style="font-weight: 500; color: var(--text-muted); font-size: 0.8rem;">({{ $employee->nik }})</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Prinsiple / Brand</span>
                    <span class="info-value" style="color: #0F52BA;">
                        {{ $tenantPrincipal->name }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Area / Cabang</span>
                    <span class="info-value">
                        {{ $employee?->branch?->name ?? '-' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Toko / Outlet Tujuan</span>
                    <span class="info-value" style="font-weight: 800;">
                        {{ $storeName }}
                    </span>
                </div>

                @if($submission->verified_at)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Diverifikasi Oleh</span>
                        <span class="info-value">
                            {{ $submission->verifier?->name ?? 'Admin Prinsiple' }}
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; font-weight: 500;">
                                {{ $submission->verified_at->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- CARD 2: VALIDASI GPS & LOKASI --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <i class="fa-solid fa-location-dot" style="color: #0F52BA;"></i>
                    <span>Lokasi & Validasi Presensi GPS</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Status Radius Toko</span>
                    <span class="info-value">
                        @if($submission->is_within_radius)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #dcfce7; color: #15803d;">
                                <i class="fa-solid fa-circle-check"></i> Dalam Radius Toko
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #fee2e2; color: #b91c1c;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Di Luar Radius Toko
                            </span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Koordinat GPS</span>
                    <span class="info-value">
                        @if($coordinates)
                            <span style="font-family: monospace; font-size: 0.82rem;">{{ $coordinates }}</span>
                            @if($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" class="media-full-btn" style="margin-left: 6px; font-size: 0.8rem; text-decoration: underline;">
                                    <span>Buka Maps ↗</span>
                                </a>
                            @endif
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Alamat Geocoding</span>
                    <span class="info-value" style="font-size: 0.82rem; line-height: 1.35; font-weight: 500;">
                        {{ $submission->address ?? ($submission->workLocation?->address ?? 'Alamat geocoding tidak tercatat') }}
                    </span>
                </div>

                @if($submission->verification_notes)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Catatan Verifikasi</span>
                        <span class="info-value" style="color: #b45309; font-style: italic;">
                            "{{ $submission->verification_notes }}"
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 2: SPLIT CONTENT (DATA FORM TABLE + PHOTO GALLERY) --}}
        <div class="content-split-grid @if($mediaValues->isEmpty()) no-media @endif">
            {{-- PANEL 1: DAFTAR PARAMETER ISIAN TEKS / DATA --}}
            <div class="panel-container">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-solid fa-list-check" style="color: #0F52BA;"></i>
                        <span>Isian & Data Formulir</span>
                    </div>
                    <span class="panel-count-badge">{{ $textValues->count() }} Parameter</span>
                </div>

                @if($textValues->isNotEmpty())
                    <table class="param-table">
                        <tbody>
                            @foreach($textValues as $val)
                                @php
                                    $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
                                    $fieldType = $val->formField?->field_type ?? $val->field_type;
                                @endphp
                                <tr>
                                    <td class="param-num-col">
                                        <span class="param-num-circle">{{ $loop->iteration }}</span>
                                    </td>
                                    <td class="param-label-col">
                                        <div class="param-label-text">{{ $fieldLabel }}</div>
                                    </td>
                                    <td class="param-val-col">
                                        @if($fieldType === 'currency' && $val->value_number !== null)
                                            <span class="val-currency">
                                                Rp {{ number_format((float)$val->value_number, 0, ',', '.') }}
                                            </span>
                                        @elseif($val->value_number !== null)
                                            <span class="val-number">
                                                {{ number_format((float)$val->value_number, (floor($val->value_number) == $val->value_number ? 0 : 2), ',', '.') }}
                                            </span>
                                        @elseif(!empty($val->value_json))
                                            <div class="val-chips-wrap">
                                                @foreach((array)$val->value_json as $chip)
                                                    <span class="val-chip">{{ $chip }}</span>
                                                @endforeach
                                            </div>
                                        @elseif(!empty($val->value_text))
                                            <span class="val-text">{{ $val->value_text }}</span>
                                        @else
                                            <span class="val-empty">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 2.5rem 1.5rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-file-lines" style="font-size: 2rem; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                        <p style="font-size: 0.88rem; margin: 0;">Tidak ada isian teks tambahan pada formulir ini.</p>
                    </div>
                @endif
            </div>

            {{-- PANEL 2: GALERI FOTO BUKTI / DOKUMENTASI --}}
            @if($mediaValues->isNotEmpty())
                <div class="panel-container">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-camera" style="color: #0F52BA;"></i>
                            <span>Foto Bukti & Dokumentasi</span>
                        </div>
                        <span class="panel-count-badge">{{ $mediaValues->count() }} Foto</span>
                    </div>

                    <div class="media-gallery-grid">
                        @foreach($mediaValues as $idx => $m)
                            <div class="media-item-card">
                                <div class="media-item-header">
                                    <span class="media-badge-tag"><i class="fa-solid fa-image"></i> Foto #{{ $loop->iteration }}</span>
                                    <div class="media-field-title">{{ $m['label'] }}</div>
                                </div>
                                <div class="media-photo-frame" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['label']) }} (Foto #{{ $loop->iteration }})')" title="Klik untuk memperbesar">
                                    <img src="{{ $m['url'] }}" alt="{{ $m['label'] }}" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Foto';">
                                </div>
                                <div class="media-footer-bar">
                                    <button type="button" class="media-full-btn" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['label']) }} (Foto #{{ $loop->iteration }})')">
                                        <i class="fa-solid fa-expand"></i> <span>Lihat Foto Penuh</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- LIGHTBOX MODAL UNTUK PREVIEW FOTO --}}
    <div id="photoLightbox" class="lightbox-backdrop" onclick="if(event.target === this) closePhotoModal()">
        <div class="lightbox-content-box">
            <div class="lightbox-header">
                <div class="lightbox-title">
                    <i class="fa-solid fa-image" style="color: #0F52BA;"></i>
                    <span id="lightboxTitle">Preview Foto Bukti</span>
                </div>
                <div class="lightbox-actions">
                    <a id="lightboxDownloadBtn" href="#" target="_blank" download class="lightbox-action-btn">
                        <i class="fa-solid fa-download"></i> <span>Unduh</span>
                    </a>
                    <button type="button" class="lightbox-close-btn" onclick="closePhotoModal()">&times;</button>
                </div>
            </div>
            <div class="lightbox-image-wrap">
                <img id="lightboxImg" src="" alt="Preview">
            </div>
        </div>
    </div>

    {{-- MODAL REJECT WITH REASON --}}
    <div id="rejectModal" class="custom-modal-backdrop" onclick="if(event.target === this) closeRejectModal()">
        <div class="custom-modal-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #b91c1c; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Tolak Laporan Ini?</span>
                </h3>
                <button type="button" onclick="closeRejectModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $submission->id, 'p' => $tenantPrincipal->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.35rem;">
                        Alasan / Catatan Penolakan (Opsional):
                    </label>
                    <textarea name="verification_notes" rows="3" placeholder="Tuliskan alasan penolakan atau catatan evaluasi untuk promotor..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">{{ $submission->verification_notes }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" onclick="closeRejectModal()" class="btn-portal-back">Batal</button>
                    <button type="submit" class="btn-action-reject">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Konfirmasi Tolak</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openPhotoModal(imageUrl, title) {
            document.getElementById('lightboxImg').src = imageUrl;
            document.getElementById('lightboxTitle').textContent = title || 'Preview Foto Bukti';
            document.getElementById('lightboxDownloadBtn').href = imageUrl;
            document.getElementById('photoLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePhotoModal() {
            document.getElementById('photoLightbox').style.display = 'none';
            document.getElementById('lightboxImg').src = '';
            document.body.style.overflow = 'auto';
        }

        function openRejectModal() {
            document.getElementById('rejectModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhotoModal();
                closeRejectModal();
            }
        });
    </script>
    @endpush
@endsection
