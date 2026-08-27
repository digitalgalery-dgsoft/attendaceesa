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

    /* SECTION HEADINGS */
    .section-header-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.5rem;
        margin-bottom: -0.25rem;
    }
    .section-header-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-count-pill {
        font-size: 0.75rem;
        font-weight: 700;
        background: rgba(15, 82, 186, 0.08);
        color: #0F52BA;
        padding: 2px 10px;
        border-radius: 999px;
    }

    /* FORM RESPONSES GRID */
    .responses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 1.25rem;
    }
    @media (max-width: 640px) {
        .responses-grid {
            grid-template-columns: 1fr;
        }
    }

    .response-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
    }
    .response-card:hover {
        border-color: var(--border-hover);
        box-shadow: var(--shadow-md);
    }

    .response-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .response-value-text {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.4;
    }

    .response-photo-box {
        margin-top: 0.75rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .response-photo-img {
        width: 100%;
        max-height: 320px;
        object-fit: contain;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
    }

    .photo-action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-muted);
        padding-top: 4px;
    }
    .photo-link {
        color: #0F52BA;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 700;
    }
    .photo-link:hover {
        text-decoration: underline;
    }

    /* EMPTY STATE */
    .empty-responses {
        background: #ffffff;
        border: 1px dashed var(--border-color);
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        color: var(--text-muted);
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
    @endphp

    <div class="report-view-wrapper">
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
                <div class="status-badge" style="background-color: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }}; border-color: {{ $statusConfig['border'] }};">
                    <i class="fa-solid {{ $statusConfig['icon'] }}"></i>
                    <span>{{ $statusConfig['label'] }}</span>
                </div>

                <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" class="btn-portal-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Rekap</span>
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
                            {{ $submission->verifier?->name ?? 'Admin / Verifikator' }}
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
                                <a href="{{ $mapsUrl }}" target="_blank" class="photo-link" style="margin-left: 6px; font-size: 0.8rem;">
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
                        <span class="info-label">Catatan Admin</span>
                        <span class="info-value" style="color: #b45309; font-style: italic;">
                            "{{ $submission->verification_notes }}"
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 2: HASIL FORM & JAWABAN ISIAN --}}
        <div class="section-header-box">
            <div class="section-header-title">
                <i class="fa-solid fa-list-check" style="color: #0F52BA;"></i>
                <span>Isian Form & Bukti Foto Hasil Pelaporan</span>
            </div>
            <span class="section-count-pill">{{ $submission->values->count() }} Parameter</span>
        </div>

        @if($submission->values->isNotEmpty())
            <div class="responses-grid">
                @foreach($submission->values as $index => $val)
                    @php
                        $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
                        $fieldType = $val->formField?->field_type ?? $val->field_type;
                        $hasMedia = !empty($val->media_url);
                        $mediaUrl = $hasMedia ? asset('storage/' . $val->media_url) : null;
                    @endphp

                    <div class="response-card">
                        <div>
                            <div class="response-label">
                                <span>#{{ $index + 1 }}</span>
                                <span>&bull;</span>
                                <span>{{ $fieldLabel }}</span>
                            </div>

                            @if(!$hasMedia)
                                <div class="response-value-text">
                                    @if($fieldType === 'currency' && $val->value_number !== null)
                                        <span style="color: #15803d; font-family: monospace; font-size: 1.15rem;">
                                            Rp {{ number_format((float)$val->value_number, 0, ',', '.') }}
                                        </span>
                                    @elseif($val->value_number !== null)
                                        <span>{{ number_format((float)$val->value_number, (floor($val->value_number) == $val->value_number ? 0 : 2), ',', '.') }}</span>
                                    @elseif(!empty($val->value_json))
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">
                                            @foreach((array)$val->value_json as $chip)
                                                <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; font-size: 0.8rem; font-weight: 600;">
                                                    {{ $chip }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif(!empty($val->value_text))
                                        <span style="white-space: pre-line;">{{ $val->value_text }}</span>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">(Tidak diisi)</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($hasMedia)
                            <div class="response-photo-box">
                                <a href="{{ $mediaUrl }}" target="_blank">
                                    <img src="{{ $mediaUrl }}" alt="{{ $fieldLabel }}" class="response-photo-img" loading="lazy" />
                                </a>
                                <div class="photo-action-bar">
                                    <span><i class="fa-solid fa-camera"></i> Lampiran Foto Bukti</span>
                                    <a href="{{ $mediaUrl }}" target="_blank" class="photo-link">
                                        <span>Buka Resolusi Asli <i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-responses">
                <i class="fa-solid fa-file-circle-xmark" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Tidak Ada Parameter Isian</div>
                <div style="font-size: 0.85rem;">Laporan ini tidak memiliki data input form yang tersimpan.</div>
            </div>
        @endif
    </div>
@endsection
