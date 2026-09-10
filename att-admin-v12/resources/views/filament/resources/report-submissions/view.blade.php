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

        // Cek apakah submission ini memiliki list kompetitor dinamis
        $hasDynamicCompetitors = false;
        foreach ($record->values as $v) {
            $fn = strtolower((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : '')));
            if ($fn === 'data_kompetitor_list') {
                $compData = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($compData) && !empty($compData)) {
                    $hasDynamicCompetitors = true;
                    break;
                }
            }
        }

        // Cek apakah submission ini memiliki list item offtake multi-produk
        $hasDynamicOfftakeItems = false;
        $offtakeItemsList = [];
        $offtakeGlobalData = [
            'total_volume_liter' => 0,
            'total_nilai_sales_rp' => 0,
            'total_volume_unit' => 0,
            'jml_customer_masuk' => null,
            'jml_customer_beli_cat' => null,
            'jml_customer_beli_dulux' => null,
            'estimasi_market_share_persen' => null,
            'tipe_laporan_offtake' => 'Sale',
        ];

        foreach ($record->values as $v) {
            $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
            if ($fn === 'offtake_items_json') {
                $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($raw) && !empty($raw)) {
                    $hasDynamicOfftakeItems = true;
                    $offtakeItemsList = $raw;
                }
            } elseif ($fn === 'total_volume_liter') {
                $offtakeGlobalData['total_volume_liter'] = (float)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'total_nilai_sales_rp') {
                $offtakeGlobalData['total_nilai_sales_rp'] = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'total_volume_unit') {
                $offtakeGlobalData['total_volume_unit'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_masuk') {
                $offtakeGlobalData['jml_customer_masuk'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_beli_cat') {
                $offtakeGlobalData['jml_customer_beli_cat'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_beli_dulux') {
                $offtakeGlobalData['jml_customer_beli_dulux'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'estimasi_market_share_persen') {
                $offtakeGlobalData['estimasi_market_share_persen'] = $v->value_text;
            } elseif ($fn === 'tipe_laporan_offtake') {
                $offtakeGlobalData['tipe_laporan_offtake'] = $v->value_text ?: 'Sale';
            }
        }

        // Selalu prioritaskan kalkulasi akumulatif dari offtake_items_json jika tersedia
        if ($hasDynamicOfftakeItems && !empty($offtakeItemsList)) {
            $calcUnit = 0; $calcLiter = 0; $calcRp = 0;
            foreach ($offtakeItemsList as $it) {
                $qT = (float)($it['qty_tin'] ?? 0);
                $qG = (float)($it['qty_galon'] ?? 0);
                $qP = (float)($it['qty_pail'] ?? 0);
                $calcUnit += (int)($it['total_unit'] ?? ($qT + $qG + $qP));
                $calcLiter += (float)($it['total_liter'] ?? (($it['volume_tin_l'] ?? 0) + ($it['volume_galon_l'] ?? 0) + ($it['volume_pail_l'] ?? 0)));
                $calcRp += (float)($it['total_nilai_rp'] ?? 0);
            }
            if ($calcUnit > 0) $offtakeGlobalData['total_volume_unit'] = $calcUnit;
            if ($calcLiter > 0) $offtakeGlobalData['total_volume_liter'] = $calcLiter;
            if ($calcRp > 0) $offtakeGlobalData['total_nilai_sales_rp'] = $calcRp;

            // Perhitungan pintar market share jika belum tercatat atau 0%
            if (empty($offtakeGlobalData['estimasi_market_share_persen']) || $offtakeGlobalData['estimasi_market_share_persen'] === '0%' || $offtakeGlobalData['estimasi_market_share_persen'] === '0') {
                $cCat = (float)($offtakeGlobalData['jml_customer_beli_cat'] ?? 0);
                $cDulux = (float)($offtakeGlobalData['jml_customer_beli_dulux'] ?? 0);
                if ($cCat > 0) {
                    $ms = round(($cDulux / $cCat) * 100);
                    $offtakeGlobalData['estimasi_market_share_persen'] = "{$ms}%";
                } elseif ($cDulux > 0) {
                    $offtakeGlobalData['estimasi_market_share_persen'] = "100%";
                }
            }
        }

        $suppressCompetitorFields = [
            'merk_kompetitor',
            'subbrand_kompetitor',
            'harga_kompetitor_tin_rp',
            'harga_kompetitor_galon_rp',
            'harga_kompetitor_pail_rp',
            'merk_kompetitor_sejenis_di_toko',
            'nama_subbrand_kompetitor_yang_dicek',
            'harga_jual_kompetitor_kemasan_galon_2.5l/4-5kg_(rp)',
            'harga_jual_kompetitor_kemasan_pail_20l/25kg_(rp)',
            'harga_jual_kompetitor_kemasan_tin_/_kaleng_1l/1kg_(rp)',
        ];

        $suppressOfftakeFields = [
            'offtake_items_json',
            'sub_brand',
            'subbrand',
            'produk_terjual',
            'subbrand_produk',
            'brand',
            'brand_rm_base',
            'sub_brand1',
            'sub_brand2',
            'pilih_produk_sub_brand',
            'pilih_produk_/_sub_brand',
            'sub_brand_spesifik_/_varian_(sub_brand_1)',
            'detail_rm_/_base_(sub_brand_2)',
            'kemasan_tin',
            'kemasan_galon',
            'kemasan_pail',
            'qty_tin',
            'qty_galon',
            'qty_pail',
            'kuantiti_tin_terjual_(unit)',
            'kuantiti_galon_terjual_(unit)',
            'kuantiti_pail_terjual_(unit)',
            'volume_tin_l',
            'volume_galon_l',
            'volume_pail_l',
            'volume_tin_(liter)',
            'volume_galon_(liter)',
            'volume_pail_(liter)',
            'total_volume_unit',
            'total_volume_liter',
            'total_nilai_sales_rp',
            'grand_total_nilai_penjualan_(rupiah)',
            'grand_total_volume_penjualan_(liter)',
            'grand_total_kuantiti_unit_(tin_+_galon_+_pail)',
            'jml_customer_masuk',
            'jml_customer_beli_cat',
            'jml_customer_beli_dulux',
            'jumlah_customer_masuk',
            'jumlah_cust_yang_beli_cat',
            'jumlah_cust_yang_beli_produk_dulux',
            'estimasi_market_share_persen',
            'estimasi_market_share_(%)',
            'tipe_transaksi_hari_ini',
            'tipe_laporan_offtake',
        ];

        // Separate text inputs and photo/media attachments to prevent tall empty grid cards
        $textValues = $record->values->filter(function($val) use ($hasDynamicCompetitors, $suppressCompetitorFields, $hasDynamicOfftakeItems, $suppressOfftakeFields) {
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);
            if ($isMedia) return false;

            if ($hasDynamicCompetitors) {
                $fn = strtolower((string)($val->field_name ?: ($val->formField ? $val->formField->field_name : '')));
                $fl = strtolower((string)($val->formField?->field_label ?? ''));
                $flClean = str_replace(' ', '_', $fl);
                if (in_array($fn, $suppressCompetitorFields) || in_array($flClean, $suppressCompetitorFields)) {
                    return false;
                }
            }

            if ($hasDynamicOfftakeItems) {
                $fn = strtolower(trim((string)($val->field_name ?: ($val->formField ? $val->formField->field_name : ''))));
                $fl = strtolower(trim((string)($val->formField?->field_label ?? '')));
                $flClean = str_replace([' ', '-', '/'], '_', $fl);
                if (in_array($fn, $suppressOfftakeFields) || in_array($flClean, $suppressOfftakeFields) || str_contains($fn, 'grand_total') || str_contains($flClean, 'grand_total')) {
                    return false;
                }
            }

            return true;
        });

        // Collect all individual media items (including multi-photo JSON array)
        $mediaItems = [];
        foreach ($record->values as $val) {
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
                $subId = $record->id;
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

            // Jika ada lebih dari 1 foto untuk field ini, beri keterangan indeks
            $totalFieldPhotos = count($foundUrls);
            foreach ($foundUrls as $fIdx => &$fItem) {
                if ($totalFieldPhotos > 1) {
                    $fItem['display_label'] = $fItem['label'] . ' (' . ($fIdx + 1) . '/' . $totalFieldPhotos . ')';
                } else {
                    $fItem['display_label'] = $fItem['label'];
                }
            }
            unset($fItem);

            $mediaItems = array_merge($mediaItems, $foundUrls);
        }
        $mediaValues = collect($mediaItems);
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

        /* OFFTAKE SUMMARY & PRODUCT BREAKDOWN STYLING */
        .offtake-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .offtake-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.15rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }
        .dark .offtake-stat-card {
            background: #0f172a;
            border-color: #1e293b;
        }
        .offtake-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .offtake-stat-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .offtake-stat-label {
            font-size: 0.74rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .dark .offtake-stat-label {
            color: #94a3b8;
        }
        .offtake-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .dark .offtake-stat-value {
            color: #f8fafc;
        }
        .offtake-stat-sub {
            font-size: 0.74rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .offtake-stat-sub {
            color: #94a3b8;
        }
        .product-breakdown-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.15rem;
            margin-bottom: 0.85rem;
            transition: all 0.15s ease;
        }
        .dark .product-breakdown-card {
            background: #0f172a;
            border-color: #1e293b;
        }
        .product-breakdown-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .dark .product-breakdown-card:hover {
            border-color: #334155;
        }
        .brand-tag {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-tag-dulux {
            background: #0F52BA;
            color: #ffffff;
        }
        .brand-tag-catylac {
            background: #ef4444;
            color: #ffffff;
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

        {{-- PANEL RINGKASAN GLOBAL TRANSAKSI OFFTAKE (AKUMULATIF GLOBAL) --}}
        @if($hasDynamicOfftakeItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <x-filament::icon icon="heroicon-o-banknotes" style="width: 24px; height: 24px;" />
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Penjualan</span>
                        <span class="offtake-stat-value" style="color: #15803d;">Rp {{ number_format($offtakeGlobalData['total_nilai_sales_rp'], 0, ',', '.') }}</span>
                        <span class="offtake-stat-sub">Akumulasi {{ count($offtakeItemsList) }} produk terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <x-filament::icon icon="heroicon-o-beaker" style="width: 24px; height: 24px;" />
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Volume</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ number_format($offtakeGlobalData['total_volume_liter'], 2, ',', '.') }} L</span>
                        <span class="offtake-stat-sub">Total volume cat terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                        <x-filament::icon icon="heroicon-o-rectangle-stack" style="width: 24px; height: 24px;" />
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kuantiti Terjual</span>
                        <span class="offtake-stat-value" style="color: #4f46e5;">{{ number_format($offtakeGlobalData['total_volume_unit']) }} Unit</span>
                        <span class="offtake-stat-sub">Akumulasi Tin + Galon + Pail</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <x-filament::icon icon="heroicon-o-user-group" style="width: 24px; height: 24px;" />
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Traffic & Pangsa Pasar</span>
                        <span class="offtake-stat-value" style="color: #b45309;">{{ $offtakeGlobalData['estimasi_market_share_persen'] ?? '0%' }}</span>
                        <span class="offtake-stat-sub" title="Cust Masuk: {{ $offtakeGlobalData['jml_customer_masuk'] ?? 0 }} | Beli Cat: {{ $offtakeGlobalData['jml_customer_beli_cat'] ?? 0 }} | Beli Dulux: {{ $offtakeGlobalData['jml_customer_beli_dulux'] ?? 0 }}">
                            Masuk: {{ $offtakeGlobalData['jml_customer_masuk'] ?? 0 }} | Beli Cat: {{ $offtakeGlobalData['jml_customer_beli_cat'] ?? 0 }} | Beli Dulux: {{ $offtakeGlobalData['jml_customer_beli_dulux'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION 2: SPLIT CONTENT (DATA FORM TABLE + PHOTO GALLERY) --}}
        <div class="content-split-grid @if($mediaValues->isEmpty()) no-media @endif">
            {{-- PANEL 1: DAFTAR PARAMETER ISIAN TEKS / DATA --}}
            <div class="panel-container">
                @if($hasDynamicOfftakeItems)
                    <div class="panel-header">
                        <div class="panel-title">
                            <x-filament::icon icon="heroicon-o-shopping-bag" style="width: 18px; height: 18px; color: #0F52BA;" />
                            <span>Rincian Produk Terjual</span>
                        </div>
                        <span class="panel-count-badge" style="background: #dbeafe; color: #1d4ed8; font-weight: 800;">
                            {{ count($offtakeItemsList) }} Produk Terjual
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($offtakeItemsList as $pIdx => $pItem)
                            @php
                                $pBrand = $pItem['brand'] ?? 'Dulux';
                                $pName = $pItem['sub_brand'] ?? ($pItem['product_name'] ?? 'Produk Cat');
                                $pSub1 = $pItem['sub_brand1'] ?? $pName;
                                $pSub2 = $pItem['sub_brand2'] ?? '-';
                                $pRmBase = $pItem['brand_rm_base'] ?? '-';
                                $hTin = (float)($pItem['harga_tin'] ?? 0);
                                $hGalon = (float)($pItem['harga_galon'] ?? 0);
                                $hPail = (float)($pItem['harga_pail'] ?? 0);
                                $qTin = (int)($pItem['qty_tin'] ?? 0);
                                $qGalon = (int)($pItem['qty_galon'] ?? 0);
                                $qPail = (int)($pItem['qty_pail'] ?? 0);
                                $vTin = (float)($pItem['volume_tin_l'] ?? 0);
                                $vGalon = (float)($pItem['volume_galon_l'] ?? 0);
                                $vPail = (float)($pItem['volume_pail_l'] ?? 0);
                                $totUnit = (int)($pItem['total_unit'] ?? ($qTin + $qGalon + $qPail));
                                $totLiter = (float)($pItem['total_liter'] ?? ($vTin + $vGalon + $vPail));
                                $totRp = (float)($pItem['total_nilai_rp'] ?? 0);
                            @endphp
                            <div class="product-breakdown-card">
                                {{-- CARD HEADER --}}
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;" class="dark:border-gray-800">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <span class="brand-tag {{ strtolower($pBrand) === 'catylac' ? 'brand-tag-catylac' : 'brand-tag-dulux' }}">
                                            {{ $pBrand }}
                                        </span>
                                        <strong style="font-size: 0.95rem; font-weight: 800;" class="text-gray-900 dark:text-gray-100">{{ $pName }}</strong>
                                        @if(!empty($pRmBase) && $pRmBase !== '-' && $pRmBase !== $pBrand)
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                                {{ $pRmBase }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span style="font-size: 0.9rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 4px 10px; border-radius: 8px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px;" class="dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300">
                                            Rp {{ number_format($totRp, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- CARD SPECS GRID --}}
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem;">
                                    {{-- 1. HARGA STANDART / ACUAN --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;" class="dark:bg-gray-850 dark:border-gray-750">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;" class="dark:text-gray-400">
                                            <x-filament::icon icon="heroicon-o-tag" style="width: 14px; height: 14px; color: #0F52BA;" />
                                            <span>Harga Standart Acuan</span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Galon ({{ $pItem['kemasan_galon'] ?? '2.5L' }}):</span>
                                                <strong style="color: {{ $hGalon > 0 ? '#1e293b' : '#94a3b8' }};" class="dark:text-gray-200">Rp {{ number_format($hGalon, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Pail ({{ $pItem['kemasan_pail'] ?? '20L' }}):</span>
                                                <strong style="color: {{ $hPail > 0 ? '#1e293b' : '#94a3b8' }};" class="dark:text-gray-200">Rp {{ number_format($hPail, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Tin ({{ $pItem['kemasan_tin'] ?? '1L' }}):</span>
                                                <strong style="color: {{ $hTin > 0 ? '#1e293b' : '#94a3b8' }};" class="dark:text-gray-200">Rp {{ number_format($hTin, 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. KUANTITI TERJUAL --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;" class="dark:bg-gray-850 dark:border-gray-750">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;" class="dark:text-gray-400">
                                            <x-filament::icon icon="heroicon-o-shopping-cart" style="width: 14px; height: 14px; color: #10b981;" />
                                            <span>Kuantiti Terjual</span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Galon:</span>
                                                <strong style="color: {{ $qGalon > 0 ? '#15803d' : '#94a3b8' }};" class="dark:text-emerald-400">{{ $qGalon }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Pail:</span>
                                                <strong style="color: {{ $qPail > 0 ? '#15803d' : '#94a3b8' }};" class="dark:text-emerald-400">{{ $qPail }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Tin:</span>
                                                <strong style="color: {{ $qTin > 0 ? '#15803d' : '#94a3b8' }};" class="dark:text-emerald-400">{{ $qTin }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;" class="dark:border-gray-700">
                                                <span style="font-weight: 700; color: #1e293b;" class="dark:text-gray-300">Total Qty:</span>
                                                <strong style="color: #0F52BA; font-weight: 800;" class="dark:text-blue-400">{{ $totUnit }} Unit</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. TOTAL DALAM LITER (VOLUME) --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;" class="dark:bg-gray-850 dark:border-gray-750">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;" class="dark:text-gray-400">
                                            <x-filament::icon icon="heroicon-o-beaker" style="width: 14px; height: 14px; color: #0284c7;" />
                                            <span>Total Volume (Liter)</span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Vol Galon:</span>
                                                <span style="font-weight: 600;" class="dark:text-gray-300">{{ number_format($vGalon, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Vol Pail:</span>
                                                <span style="font-weight: 600;" class="dark:text-gray-300">{{ number_format($vPail, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;" class="dark:text-gray-400">Vol Tin:</span>
                                                <span style="font-weight: 600;" class="dark:text-gray-300">{{ number_format($vTin, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;" class="dark:border-gray-700">
                                                <span style="font-weight: 700; color: #1e293b;" class="dark:text-gray-300">Total Volume:</span>
                                                <strong style="color: #0284c7; font-weight: 800;" class="dark:text-sky-400">{{ number_format($totLiter, 2) }} Liter</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid #e2e8f0; padding: 0.75rem 1.25rem 0.25rem 1.25rem;" class="dark:border-gray-800">
                            <span style="font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @else
                    <div class="panel-header">
                        <div class="panel-title">
                            <x-filament::icon icon="heroicon-o-list-bullet" style="width: 18px; height: 18px; color: #0F52BA;" />
                            <span>Isian & Data Formulir</span>
                        </div>
                        <span class="panel-count-badge">{{ $textValues->count() }} Parameter</span>
                    </div>
                @endif

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
                                        @php
                                            $isCompList = ($val->field_name === 'data_kompetitor_list' || ($val->formField && $val->formField->field_name === 'data_kompetitor_list'));
                                            $parsedCompList = null;
                                            if ($isCompList || (is_string($val->value_text) && str_starts_with(trim($val->value_text), '[{') && str_contains($val->value_text, 'harga_'))) {
                                                $parsedCompList = is_array($val->value_json) ? $val->value_json : json_decode($val->value_text, true);
                                            }
                                        @endphp
                                        @if(!empty($parsedCompList) && is_array($parsedCompList))
                                            <div style="display: flex; flex-direction: column; gap: 8px; margin: 4px 0;">
                                                @foreach($parsedCompList as $cIdx => $cItem)
                                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;" class="dark:bg-gray-800 dark:border-gray-700">
                                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                                <span style="font-size: 0.72rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 6px; border-radius: 4px;">#{{ $cIdx + 1 }}</span>
                                                                <span style="font-size: 0.85rem; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;" class="dark:bg-sky-950 dark:text-sky-300">
                                                                    {{ $cItem['merk'] ?? $cItem['brand'] ?? 'Kompetitor' }}
                                                                </span>
                                                            </div>
                                                            @if(!empty($cItem['subbrand']))
                                                                <span style="font-size: 0.82rem; font-weight: 700; color: #334155;" class="dark:text-gray-200">
                                                                    {{ $cItem['subbrand'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.8rem;">
                                                            @if(isset($cItem['harga_galon']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;" class="dark:bg-gray-900 dark:border-gray-700">
                                                                    <span style="color: #64748b; font-size: 0.75rem;" class="dark:text-gray-400">Galon:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_galon'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_galon'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_tin']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;" class="dark:bg-gray-900 dark:border-gray-700">
                                                                    <span style="color: #64748b; font-size: 0.75rem;" class="dark:text-gray-400">Tin:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_tin'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_tin'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_pail']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;" class="dark:bg-gray-900 dark:border-gray-700">
                                                                    <span style="color: #64748b; font-size: 0.75rem;" class="dark:text-gray-400">Pail:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_pail'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_pail'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($fieldType === 'currency' && $val->value_number !== null)
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
                @elseif(!$hasDynamicOfftakeItems)
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
                        @foreach($mediaValues as $idx => $m)
                            <div class="media-item-card">
                                <div class="media-item-header">
                                    <span class="media-badge-tag">📷 Foto #{{ $loop->iteration }}</span>
                                    <div class="media-field-title">{{ $m['display_label'] ?? $m['label'] }}</div>
                                </div>
                                <div class="media-photo-frame" onclick="openAdminPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')" title="Klik untuk memperbesar">
                                    <img src="{{ $m['url'] }}" alt="{{ $m['label'] }}" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Foto';">
                                </div>
                                <div class="media-footer-bar">
                                    <button type="button" class="media-full-btn" onclick="openAdminPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')">
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
