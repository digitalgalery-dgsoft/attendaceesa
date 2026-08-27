<x-filament-panels::page>
    @php
        $record = $this->record;
        $record->loadMissing([
            'template.fields',
            'principal',
            'employee.branch',
            'employee.position',
            'workLocation',
            'itineraryItem',
            'values.formField',
            'verifier',
        ]);

        $status = $record->status ?? 'pending';
        $statusConfig = match ($status) {
            'approved', 'verified' => [
                'label' => 'Terverifikasi (Valid)',
                'bg' => '#dcfce7',
                'color' => '#15803d',
                'border' => '#86efac',
                'icon' => 'heroicon-o-check-circle',
            ],
            'rejected' => [
                'label' => 'Ditolak (Tidak Sesuai)',
                'bg' => '#fee2e2',
                'color' => '#b91c1c',
                'border' => '#fca5a5',
                'icon' => 'heroicon-o-x-circle',
            ],
            default => [
                'label' => 'Menunggu Verifikasi',
                'bg' => '#fef3c7',
                'color' => '#b45309',
                'border' => '#fde68a',
                'icon' => 'heroicon-o-clock',
            ],
        };

        $employee = $record->employee;
        $template = $record->template;
        $principal = $record->principal;
        $workLocation = $record->workLocation ?? $record->itineraryItem;
        $storeName = $record->workLocation?->name ?? $record->itineraryItem?->destination ?? $record->store_name ?? 'Kunjungan Toko';
        $coordinates = ($record->latitude && $record->longitude) ? "{$record->latitude}, {$record->longitude}" : null;
        $mapsUrl = $coordinates ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" : null;

        // Separate text inputs and photo/media attachments to prevent tall empty grid cards
        $textValues = $record->values->filter(function($val) {
            return empty($val->media_url) && empty($val->file_path);
        });
        $mediaValues = $record->values->filter(function($val) {
            return !empty($val->media_url) || !empty($val->file_path);
        });
    @endphp

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
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }
        .dark .report-banner-card {
            background: #0f172a;
            border-color: #1e293b;
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
        .dark .banner-icon-box {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(59, 130, 246, 0.15) 100%);
            color: #60a5fa;
            border-color: #1e3a8a;
        }

        .banner-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .dark .banner-title {
            color: #f8fafc;
        }

        .banner-subtitle {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            color: #64748b;
            flex-wrap: wrap;
        }
        .dark .banner-subtitle {
            color: #94a3b8;
        }

        .code-badge {
            font-family: monospace;
            font-weight: 700;
            background: #f1f5f9;
            color: #0284c7;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 0.82rem;
        }
        .dark .code-badge {
            background: #1e293b;
            border-color: #334155;
            color: #38bdf8;
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
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .dark .overview-card {
            background: #0f172a;
            border-color: #1e293b;
        }

        .overview-card-title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .overview-card-title {
            color: #94a3b8;
            border-bottom-color: #1e293b;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.88rem;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
            min-width: 120px;
            font-size: 0.82rem;
        }
        .dark .info-label {
            color: #94a3b8;
        }
        .info-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }
        .dark .info-value {
            color: #f8fafc;
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
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .dark .panel-container {
            background: #0f172a;
            border-color: #1e293b;
        }

        .panel-header {
            padding: 1.15rem 1.35rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .panel-header {
            background: #1e293b;
            border-color: #334155;
        }

        .panel-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .panel-title {
            color: #f8fafc;
        }

        .panel-count-badge {
            font-size: 0.74rem;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .dark .panel-count-badge {
            background: #082f49;
            color: #38bdf8;
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
        .dark .param-table tr {
            border-color: #1e293b;
        }
        .param-table tr:last-child {
            border-bottom: none;
        }
        .param-table tr:hover {
            background: #f8fafc;
        }
        .dark .param-table tr:hover {
            background: #1e293b;
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
        .dark .param-num-circle {
            background: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }

        .param-label-col {
            padding-left: 0.85rem !important;
        }
        .param-label-text {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .dark .param-label-text {
            color: #f8fafc;
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
            color: #0f172a;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 6px;
            display: inline-block;
        }
        .dark .val-number {
            background: #1e293b;
            color: #f8fafc;
        }
        .val-text {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
            word-break: break-word;
        }
        .dark .val-text {
            color: #f8fafc;
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
            color: #0f172a;
        }
        .dark .val-chip {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
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
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .dark .media-item-card {
            background: #1e293b;
            border-color: #334155;
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
        .dark .media-badge-tag {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.15);
        }
        .media-field-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
            text-align: right;
            flex: 1;
        }
        .dark .media-field-title {
            color: #f8fafc;
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
        .dark .media-photo-frame {
            background: #0f172a;
            border-color: #334155;
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
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.15s ease;
        }
        .media-full-btn:hover {
            background: rgba(15, 82, 186, 0.08);
            text-decoration: underline;
        }
        .dark .media-full-btn {
            color: #60a5fa;
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
        .dark .lightbox-content-box {
            background: #1e293b;
        }
        @keyframes zoomIn {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .lightbox-header {
            padding: 1rem 1.35rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .dark .lightbox-header {
            background: #0f172a;
            border-color: #334155;
        }
        .lightbox-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .lightbox-title {
            color: #f8fafc;
        }
        .lightbox-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .lightbox-action-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s ease;
        }
        .dark .lightbox-action-btn {
            background: #334155;
            color: #f8fafc;
            border-color: #475569;
        }
        .lightbox-close-btn {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #64748b;
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
    </style>

    <div class="report-view-wrapper">
        {{-- BANNER HEADER CARD --}}
        <div class="report-banner-card">
            <div class="banner-left">
                <div class="banner-icon-box">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" style="width: 28px; height: 28px;" />
                </div>
                <div>
                    <div class="banner-title">
                        {{ $template?->title ?? 'Laporan Pelaporan' }}
                    </div>
                    <div class="banner-subtitle">
                        <span>No. Laporan:</span>
                        <span class="code-badge">{{ $record->submission_code }}</span>
                        <span>&bull;</span>
                        <span>Disubmit pada: <strong>{{ $record->submitted_at ? $record->submitted_at->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}</strong></span>
                    </div>
                </div>
            </div>

            <div>
                <div class="status-badge" style="background-color: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }}; border-color: {{ $statusConfig['border'] }};">
                    <x-filament::icon :icon="$statusConfig['icon']" style="width: 18px; height: 18px;" />
                    <span>{{ $statusConfig['label'] }}</span>
                </div>
            </div>
        </div>

        {{-- 2-COLUMN OVERVIEW GRID --}}
        <div class="overview-grid">
            {{-- CARD 1: INFORMASI PROMOTOR & OUTLET --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <x-filament::icon icon="heroicon-o-user-circle" style="width: 16px; height: 16px; color: #0F52BA;" />
                    <span>Informasi Pelapor & Toko</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Nama Promotor / SPG</span>
                    <span class="info-value">
                        {{ $employee?->full_name ?? '-' }}
                        @if($employee?->nik)
                            <span style="font-weight: 500; color: #64748b; font-size: 0.8rem;">({{ $employee->nik }})</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Prinsiple / Brand</span>
                    <span class="info-value" style="color: #0F52BA;">
                        {{ $principal?->name ?? '-' }}
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

                @if($record->verified_at)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Diverifikasi Oleh</span>
                        <span class="info-value">
                            {{ $record->verifier?->name ?? 'Admin' }}
                            <span style="font-size: 0.75rem; color: #64748b; display: block; font-weight: 500;">
                                {{ $record->verified_at->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- CARD 2: VALIDASI GPS & LOKASI --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <x-filament::icon icon="heroicon-o-map-pin" style="width: 16px; height: 16px; color: #0F52BA;" />
                    <span>Lokasi & Validasi Presensi GPS</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Status Radius Toko</span>
                    <span class="info-value">
                        @if($record->is_within_radius)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #dcfce7; color: #15803d;">
                                🟢 Dalam Radius Toko
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #fee2e2; color: #b91c1c;">
                                ⚠️ Di Luar Radius Toko
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
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Alamat Geocoding</span>
                    <span class="info-value" style="font-size: 0.82rem; line-height: 1.35; font-weight: 500;">
                        {{ $record->address ?? 'Alamat geocoding tidak tercatat' }}
                    </span>
                </div>

                @if($record->verification_notes)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Catatan Admin</span>
                        <span class="info-value" style="color: #b45309; font-style: italic;">
                            "{{ $record->verification_notes }}"
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
                        <x-filament::icon icon="heroicon-o-list-bullet" style="width: 18px; height: 18px; color: #0F52BA;" />
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
                    <div style="padding: 2.5rem 1.5rem; text-align: center; color: #64748b;">
                        <x-filament::icon icon="heroicon-o-document-text" style="width: 32px; height: 32px; margin: 0 auto 8px; color: #94a3b8;" />
                        <p style="font-size: 0.88rem; margin: 0;">Tidak ada isian teks tambahan pada formulir ini.</p>
                    </div>
                @endif
            </div>

            {{-- PANEL 2: GALERI FOTO BUKTI / DOKUMENTASI --}}
            @if($mediaValues->isNotEmpty())
                <div class="panel-container">
                    <div class="panel-header">
                        <div class="panel-title">
                            <x-filament::icon icon="heroicon-o-camera" style="width: 18px; height: 18px; color: #0F52BA;" />
                            <span>Foto Bukti & Dokumentasi</span>
                        </div>
                        <span class="panel-count-badge">{{ $mediaValues->count() }} Foto</span>
                    </div>

                    <div class="media-gallery-grid">
                        @foreach($mediaValues as $val)
                            @php
                                $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
                                $mediaPath = $val->media_url ?? $val->file_path;
                                $mediaUrl = asset('storage/' . $mediaPath);
                            @endphp
                            <div class="media-item-card">
                                <div class="media-item-header">
                                    <span class="media-badge-tag">📷 Foto #{{ $loop->iteration }}</span>
                                    <div class="media-field-title">{{ $fieldLabel }}</div>
                                </div>
                                <div class="media-photo-frame" onclick="openAdminPhotoModal('{{ $mediaUrl }}', '{{ addslashes($fieldLabel) }}')" title="Klik untuk memperbesar">
                                    <img src="{{ $mediaUrl }}" alt="{{ $fieldLabel }}" loading="lazy">
                                </div>
                                <div class="media-footer-bar">
                                    <button type="button" class="media-full-btn" onclick="openAdminPhotoModal('{{ $mediaUrl }}', '{{ addslashes($fieldLabel) }}')">
                                        <span>Lihat Foto Penuh ↗</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- LIGHTBOX MODAL UNTUK PREVIEW FOTO ADMIN --}}
    <div id="adminPhotoLightbox" class="lightbox-backdrop" onclick="if(event.target === this) closeAdminPhotoModal()">
        <div class="lightbox-content-box">
            <div class="lightbox-header">
                <div class="lightbox-title">
                    <x-filament::icon icon="heroicon-o-photo" style="width: 20px; height: 20px; color: #0F52BA;" />
                    <span id="adminLightboxTitle">Preview Foto Bukti</span>
                </div>
                <div class="lightbox-actions">
                    <a id="adminLightboxDownloadBtn" href="#" target="_blank" download class="lightbox-action-btn">
                        <span>Unduh File</span>
                    </a>
                    <button type="button" class="lightbox-close-btn" onclick="closeAdminPhotoModal()">&times;</button>
                </div>
            </div>
            <div class="lightbox-image-wrap">
                <img id="adminLightboxImg" src="" alt="Preview">
            </div>
        </div>
    </div>

    <script>
        function openAdminPhotoModal(imageUrl, title) {
            const modal = document.getElementById('adminPhotoLightbox');
            if (modal) {
                document.getElementById('adminLightboxImg').src = imageUrl;
                document.getElementById('adminLightboxTitle').textContent = title || 'Preview Foto Bukti';
                document.getElementById('adminLightboxDownloadBtn').href = imageUrl;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeAdminPhotoModal() {
            const modal = document.getElementById('adminPhotoLightbox');
            if (modal) {
                modal.style.display = 'none';
                document.getElementById('adminLightboxImg').src = '';
                document.body.style.overflow = 'auto';
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAdminPhotoModal();
            }
        });
    </script>
</x-filament-panels::page>
