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
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .section-header-title {
            color: #f8fafc;
        }
        .section-count-pill {
            font-size: 0.75rem;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 10px;
            border-radius: 999px;
        }
        .dark .section-count-pill {
            background: #082f49;
            color: #38bdf8;
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
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem 1.35rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .dark .response-card {
            background: #0f172a;
            border-color: #1e293b;
        }
        .response-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .dark .response-card:hover {
            border-color: #334155;
        }

        .response-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dark .response-label {
            color: #94a3b8;
        }

        .response-value-text {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
        }
        .dark .response-value-text {
            color: #f8fafc;
        }

        .response-photo-box {
            margin-top: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .dark .response-photo-box {
            background: #1e293b;
            border-color: #334155;
        }

        .response-photo-img {
            width: 100%;
            max-height: 320px;
            object-fit: contain;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
        .dark .response-photo-img {
            background: #0f172a;
            border-color: #334155;
        }

        .photo-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            padding-top: 4px;
        }
        .dark .photo-action-bar {
            color: #94a3b8;
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
        .dark .photo-link {
            color: #60a5fa;
        }

        /* EMPTY STATE */
        .empty-responses {
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            color: #64748b;
        }
        .dark .empty-responses {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
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
                                <a href="{{ $mapsUrl }}" target="_blank" class="photo-link" style="margin-left: 6px; font-size: 0.8rem;">
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

        {{-- SECTION 2: HASIL FORM & JAWABAN ISIAN --}}
        <div class="section-header-box">
            <div class="section-header-title">
                <x-filament::icon icon="heroicon-o-document-text" style="width: 20px; height: 20px; color: #0F52BA;" />
                <span>Isian Form & Bukti Foto Hasil Pelaporan</span>
            </div>
            <span class="section-count-pill">{{ $record->values->count() }} Parameter</span>
        </div>

        @if($record->values->isNotEmpty())
            <div class="responses-grid">
                @foreach($record->values as $index => $val)
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
                                        <span style="color: #94a3b8; font-style: italic;">(Tidak diisi)</span>
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
                                    <span>📷 Lampiran Foto / Gambar</span>
                                    <a href="{{ $mediaUrl }}" target="_blank" class="photo-link">
                                        <span>Buka Resolusi Asli ↗</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-responses">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" style="width: 48px; height: 48px; margin: 0 auto 12px; color: #94a3b8;" />
                <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Tidak Ada Parameter Isian</div>
                <div style="font-size: 0.85rem;">Laporan ini tidak memiliki data input form yang tersimpan.</div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
