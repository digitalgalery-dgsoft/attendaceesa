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

    /* OFFTAKE SUMMARY & PRODUCT BREAKDOWN STYLING */
    .offtake-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .offtake-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        overflow: hidden;
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
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .offtake-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .offtake-stat-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-breakdown-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem 1.15rem;
        margin-bottom: 0.85rem;
        transition: all 0.15s ease;
    }
    .product-breakdown-card:hover {
        border-color: #cbd5e1;
        box-shadow: var(--shadow-sm);
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

        // Cek apakah submission ini memiliki list kompetitor dinamis
        $hasDynamicCompetitors = false;
        foreach ($submission->values as $v) {
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

        // Cek apakah submission ini memiliki list item OOS multi-produk
        $hasDynamicOosItems = false;
        $oosItemsList = [];
        $oosGlobalData = [
            'total_sku_oos' => 0,
            'max_lama_oos' => 0,
            'total_saran_qty' => 0,
            'tipe_laporan_oos' => 'OOS',
        ];

        foreach ($submission->values as $v) {
            $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
            if ($fn === 'offtake_items_json') {
                $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($raw) && !empty($raw)) {
                    $hasDynamicOfftakeItems = true;
                    $offtakeItemsList = $raw;
                }
            } elseif ($fn === 'oos_items_json') {
                $rawOos = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($rawOos) && !empty($rawOos)) {
                    $hasDynamicOosItems = true;
                    $oosItemsList = $rawOos;
                }
            } elseif ($fn === 'tipe_laporan_oos') {
                $oosGlobalData['tipe_laporan_oos'] = $v->value_text ?: 'OOS';
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

        if ($hasDynamicOosItems && !empty($oosItemsList)) {
            $oosGlobalData['total_sku_oos'] = count($oosItemsList);
            $oosGlobalData['max_lama_oos'] = max(array_map(fn($it) => max(1, (int)($it['lama_oos_hari'] ?? 1)), $oosItemsList) ?: [1]);
            $oosGlobalData['total_saran_qty'] = array_sum(array_column($oosItemsList, 'saran_qty_order') ?: [0]);
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

        $suppressOosFields = [
            'oos_items_json',
            'tipe_laporan_oos',
            'produk_oos',
            'nama_produk_yang_kosong_oos',
            'pilih_produk_dulux_yang_mengalami_out_of_stock_oos',
            'kemasan_size_oos',
            'ukuran_kemasan_size',
            'kemasan_size_yang_kosong',
            'base_warna_oos',
            'base_tipe_warna',
            'base_kategori_warna_yang_kosong',
            'warna_ready_mix_oos',
            'lama_oos_hari',
            'lama_kondisi_barang_kosong_jumlah_hari',
            'lama_kondisi_oos_jumlah_hari',
            'saran_qty_order',
            'saran_kuantiti_order_ke_toko_qty_kemasan',
            'saran_kuantitas_order_qty_kaleng',
            'alasan_oos',
            'penyebab_alasan_out_of_stock_oos',
        ];

        // Separate text inputs and photo/media attachments to prevent tall empty grid cards
        $textValues = $submission->values->filter(function($val) use ($hasDynamicCompetitors, $suppressCompetitorFields, $hasDynamicOfftakeItems, $suppressOfftakeFields, $hasDynamicOosItems, $suppressOosFields) {
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);
            if ($isMedia) return false;

            $fn = strtolower(trim((string)($val->field_name ?: ($val->formField ? $val->formField->field_name : ''))));
            $fl = strtolower(trim((string)($val->formField?->field_label ?? '')));
            $flClean = str_replace([' ', '-', '/'], '_', $fl);

            if ($fn === 'oos_items_json' || $fn === 'offtake_items_json') {
                return false;
            }

            if ($hasDynamicCompetitors) {
                if (in_array($fn, $suppressCompetitorFields) || in_array($flClean, $suppressCompetitorFields)) {
                    return false;
                }
            }

            if ($hasDynamicOfftakeItems) {
                if (in_array($fn, $suppressOfftakeFields) || in_array($flClean, $suppressOfftakeFields) || str_contains($fn, 'grand_total') || str_contains($flClean, 'grand_total')) {
                    return false;
                }
            }

            if ($hasDynamicOosItems) {
                if (in_array($fn, $suppressOosFields) || in_array($flClean, $suppressOosFields)) {
                    return false;
                }
            }

            return true;
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
                <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id, 'tab' => 'live']) }}" class="btn-portal-back">
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

                @php
                    $duluxAreaToRsmMap = [
                        'ACEH' => 'North Sumatera', 'MEDAN' => 'North Sumatera', 'BATAM' => 'Central Sumatera',
                        'PADANG' => 'Central Sumatera', 'PEKANBARU' => 'Central Sumatera', 'LAMPUNG' => 'South Sumatera',
                        'PALEMBANG' => 'South Sumatera', 'JAMBI' => 'South Sumatera', 'BENGKULU' => 'South Sumatera',
                        'BALIKPAPAN' => 'Kalimantan', 'SAMARINDA' => 'Kalimantan', 'BONTANG' => 'Kalimantan',
                        'BANJARMASIN' => 'Kalimantan', 'PONTIANAK' => 'Kalimantan', 'KENDARI' => 'Sulawesi',
                        'MAKASSAR' => 'Sulawesi', 'MANADO' => 'Sulawesi', 'PALU' => 'Sulawesi',
                        'MALUKU' => 'Sulawesi', 'PAPUA' => 'Sulawesi', 'CIBUBUR' => 'Greater Jakarta',
                        'GARUT' => 'West Java', 'BANDUNG' => 'West Java', 'CIREBON' => 'West Java',
                        'TASIKMALAYA' => 'West Java', 'BOGOR' => 'Greater Jakarta', 'BEKASI' => 'Greater Jakarta',
                        'DEPOK' => 'Greater Jakarta', 'TANGERANG' => 'Greater Jakarta', 'JAKARTA BARAT' => 'Greater Jakarta',
                        'JAKARTA PUSAT' => 'Greater Jakarta', 'JAKARTA UTARA' => 'Greater Jakarta', 'JAKARTA TIMUR' => 'Greater Jakarta',
                        'JAKARTA SELATAN' => 'Greater Jakarta', 'JAKARTA' => 'Greater Jakarta', 'MADIUN' => 'East Java',
                        'SURABAYA' => 'East Java', 'MALANG' => 'East Java', 'KEDIRI' => 'East Java',
                        'BANYUWANGI' => 'East Java', 'JEMBER' => 'Bali Nusra', 'BALI' => 'Bali Nusra',
                        'LOMBOK' => 'Bali Nusra', 'KUPANG' => 'Bali Nusra', 'SEMARANG' => 'North Central Java',
                        'TEGAL' => 'North Central Java', 'PEKALONGAN' => 'North Central Java', 'KUDUS' => 'North Central Java',
                        'SOLO' => 'South Central Java', 'YOGYAKARTA' => 'South Central Java', 'PURWOKERTO' => 'South Central Java',
                        'MAGELANG' => 'South Central Java', 'CENTRAL JAVA' => 'Central Java',
                    ];
                    $storeArea = $submission->workLocation?->branch?->name ?? ($submission->workLocation?->area?->name ?? ($submission->workLocation?->area ?? ($employee?->branch?->name ?? '-')));
                    $cleanArea = strtoupper(trim((string)$storeArea));
                    $rsmDulux = $duluxAreaToRsmMap[$cleanArea] ?? null;
                    if (!$rsmDulux) {
                        foreach ($duluxAreaToRsmMap as $city => $r) {
                            if ($city !== '' && str_contains($cleanArea, $city)) { $rsmDulux = $r; break; }
                        }
                    }
                    if (!$rsmDulux && !empty($submission->workLocation?->region) && $submission->workLocation?->region !== '-') {
                        $rsmDulux = $submission->workLocation?->region;
                    }
                    $rsmDulux = $rsmDulux ?: '-';
                @endphp
                <div class="info-row">
                    <span class="info-label">Area / Kota</span>
                    <span class="info-value">
                        {{ $storeArea }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">RSM Dulux</span>
                    <span class="info-value">
                        <span style="background: rgba(15, 82, 186, 0.1); color: #0F52BA; font-weight: 700; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem;">{{ $rsmDulux }}</span>
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

        {{-- PANEL RINGKASAN GLOBAL TRANSAKSI OFFTAKE (AKUMULATIF GLOBAL) --}}
        @if($hasDynamicOfftakeItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Penjualan</span>
                        <span class="offtake-stat-value" style="color: #15803d;">Rp {{ number_format($offtakeGlobalData['total_nilai_sales_rp'], 0, ',', '.') }}</span>
                        <span class="offtake-stat-sub">Akumulasi {{ count($offtakeItemsList) }} produk terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <i class="fa-solid fa-fill-drip"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Volume</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ number_format($offtakeGlobalData['total_volume_liter'], 2, ',', '.') }} L</span>
                        <span class="offtake-stat-sub">Total volume cat terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kuantiti Terjual</span>
                        <span class="offtake-stat-value" style="color: #4f46e5;">{{ number_format($offtakeGlobalData['total_volume_unit']) }} Unit</span>
                        <span class="offtake-stat-sub">Akumulasi Tin + Galon + Pail</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <i class="fa-solid fa-users-line"></i>
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
        {{-- BANNER KHUSUS JIKA TOKO BEBAS OOS (STOK LENGKAP) --}}
        @if(strtolower($oosGlobalData['tipe_laporan_oos'] ?? '') === 'no_oos')
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.85rem; flex-shrink: 0; border: 1px solid #bbf7d0;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h3 style="margin: 0 0 0.25rem 0; font-size: 1.15rem; font-weight: 800; color: #166534;">
                        No OOS — Stok Dulux Lengkap & Prima
                    </h3>
                    <p style="margin: 0; font-size: 0.88rem; color: #15803d; line-height: 1.45;">
                        Promotor / SPG melaporkan bahwa outlet ini memiliki ketersediaan stok seluruh lini produk Dulux secara lengkap. Tidak ada produk yang mengalami Out of Stock (OOS) pada kunjungan ini.
                    </p>
                </div>
            </div>
        @endif

        {{-- PANEL RINGKASAN GLOBAL OUT OF STOCK (OOS) --}}
        @if($hasDynamicOosItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total SKU Out of Stock</span>
                        <span class="offtake-stat-value" style="color: #dc2626;">{{ count($oosItemsList) }} SKU</span>
                        <span class="offtake-stat-sub">Produk kosong terdata di outlet</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Durasi OOS Terlama</span>
                        <span class="offtake-stat-value" style="color: #ea580c;">{{ $oosGlobalData['max_lama_oos'] }} Hari</span>
                        <span class="offtake-stat-sub">Maksimal durasi kekosongan stok</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Saran Reorder</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ $oosGlobalData['total_saran_qty'] }} Unit</span>
                        <span class="offtake-stat-sub">Rekomendasi kuantiti order toko</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION 2: SPLIT CONTENT (DATA FORM TABLE + PHOTO GALLERY) --}}
        <div class="content-split-grid @if($mediaValues->isEmpty()) no-media @endif">
            {{-- PANEL 1: RINCIAN PRODUK TERJUAL (ATAU PARAMETER FORMULIR STANDAR) --}}
            <div class="panel-container">
                @if($hasDynamicOfftakeItems)
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-basket-shopping" style="color: #0F52BA;"></i>
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
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <span class="brand-tag {{ strtolower($pBrand) === 'catylac' ? 'brand-tag-catylac' : 'brand-tag-dulux' }}" style="font-size: 0.78rem; font-weight: 800; padding: 2px 8px; border-radius: 6px;">
                                            {{ $pBrand }}
                                        </span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $pName }}</strong>
                                        @if(!empty($pRmBase) && $pRmBase !== '-' && $pRmBase !== $pBrand)
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                                {{ $pRmBase }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span style="font-size: 0.9rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 4px 10px; border-radius: 8px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-rupiah-sign" style="font-size: 0.78rem;"></i>
                                            Rp {{ number_format($totRp, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- CARD SPECS GRID --}}
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem;">
                                    {{-- 1. HARGA STANDART / ACUAN --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-tag" style="color: #0F52BA;"></i> Harga Standart Acuan
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Galon ({{ $pItem['kemasan_galon'] ?? '2.5L' }}):</span>
                                                <strong style="color: {{ $hGalon > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hGalon, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Pail ({{ $pItem['kemasan_pail'] ?? '20L' }}):</span>
                                                <strong style="color: {{ $hPail > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hPail, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Tin ({{ $pItem['kemasan_tin'] ?? '1L' }}):</span>
                                                <strong style="color: {{ $hTin > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hTin, 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. KUANTITI TERJUAL --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-cart-shopping" style="color: #10b981;"></i> Kuantiti Terjual
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Galon:</span>
                                                <strong style="color: {{ $qGalon > 0 ? '#15803d' : '#94a3b8' }};">{{ $qGalon }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Pail:</span>
                                                <strong style="color: {{ $qPail > 0 ? '#15803d' : '#94a3b8' }};">{{ $qPail }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Tin:</span>
                                                <strong style="color: {{ $qTin > 0 ? '#15803d' : '#94a3b8' }};">{{ $qTin }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;">
                                                <span style="font-weight: 700; color: #1e293b;">Total Qty:</span>
                                                <strong style="color: #0F52BA; font-weight: 800;">{{ $totUnit }} Unit</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. TOTAL DALAM LITER (VOLUME) --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-fill-drip" style="color: #0284c7;"></i> Total Volume (Liter)
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Galon:</span>
                                                <span style="font-weight: 600;">{{ number_format($vGalon, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Pail:</span>
                                                <span style="font-weight: 600;">{{ number_format($vPail, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Tin:</span>
                                                <span style="font-weight: 600;">{{ number_format($vTin, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;">
                                                <span style="font-weight: 700; color: #1e293b;">Total Volume:</span>
                                                <strong style="color: #0284c7; font-weight: 800;">{{ number_format($totLiter, 2) }} Liter</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @elseif($hasDynamicOosItems)
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-boxes-packing" style="color: #dc2626;"></i>
                            <span>Rincian Produk Out of Stock (OOS)</span>
                        </div>
                        <span class="panel-count-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 800;">
                            {{ count($oosItemsList) }} Produk OOS
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($oosItemsList as $pIdx => $pItem)
                            @php
                                $pName = $pItem['product_name'] ?? ($pItem['produk_oos'] ?? 'Produk Dulux');
                                $kemasan = $pItem['kemasan_size_oos'] ?? '-';
                                $base = $pItem['base_warna_oos'] ?? '-';
                                $readyMix = $pItem['warna_ready_mix_oos'] ?? '-';
                                $lama = max(1, (int)($pItem['lama_oos_hari'] ?? ($pItem['calculated_lama_oos'] ?? 1)));
                                $saran = (int)($pItem['saran_qty_order'] ?? 0);
                                $alasan = $pItem['alasan_oos'] ?? 'PO belum kirim / kendala stok';
                            @endphp
                            <div class="product-breakdown-card" style="border-left: 4px solid {{ $lama > 3 ? '#dc2626' : ($lama > 1 ? '#ea580c' : '#2563eb') }};">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #dc2626; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $pName }}</strong>
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                            {{ $kemasan }}
                                        </span>
                                    </div>
                                    <div>
                                        <span style="font-size: 0.82rem; font-weight: 800; color: {{ $lama > 3 ? '#dc2626' : ($lama > 1 ? '#ea580c' : '#2563eb') }}; background: {{ $lama > 3 ? '#fee2e2' : ($lama > 1 ? '#ffedd5' : '#eff6ff') }}; padding: 3px 9px; border-radius: 6px; border: 1px solid {{ $lama > 3 ? '#fecaca' : ($lama > 1 ? '#fed7aa' : '#bfdbfe') }}; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-clock"></i>
                                            Lama OOS: {{ $lama }} Hari{{ $lama === 1 ? ' (Baru)' : '' }}
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Base / Varian Warna
                                        </div>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 0.85rem;">
                                            {{ $base }}
                                        </div>
                                        @if(!empty($readyMix) && $readyMix !== '-' && !str_contains($readyMix, 'Bukan'))
                                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">
                                                Warna Ready Mix: <strong style="color: #0b3d88;">{{ $readyMix }}</strong>
                                            </div>
                                        @endif
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Saran Kuantiti Order
                                        </div>
                                        <div style="font-weight: 700; color: #0b3d88; font-size: 0.85rem;">
                                            {{ $saran > 0 ? ($saran . ' Unit / Kaleng') : '-' }}
                                        </div>
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; grid-column: 1 / -1;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Penyebab / Alasan Out of Stock (OOS)
                                        </div>
                                        <div style="font-weight: 600; color: #b91c1c; font-size: 0.82rem;">
                                            {{ $alasan }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @else
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-list-check" style="color: #0F52BA;"></i>
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
                                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                                <span style="font-size: 0.72rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 6px; border-radius: 4px;">#{{ $cIdx + 1 }}</span>
                                                                <span style="font-size: 0.85rem; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">
                                                                    {{ $cItem['merk'] ?? $cItem['brand'] ?? 'Kompetitor' }}
                                                                </span>
                                                            </div>
                                                            @if(!empty($cItem['subbrand']))
                                                                <span style="font-size: 0.82rem; font-weight: 700; color: #334155;">
                                                                    {{ $cItem['subbrand'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.8rem;">
                                                            @if(isset($cItem['harga_galon']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Galon:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_galon'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_galon'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_tin']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Tin:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_tin'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_tin'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_pail']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Pail:</span>
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
                                    <div class="media-field-title">{{ $m['display_label'] ?? $m['label'] }}</div>
                                </div>
                                <div class="media-photo-frame" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')" title="Klik untuk memperbesar">
                                    <img src="{{ $m['url'] }}" alt="{{ $m['label'] }}" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Foto';">
                                </div>
                                <div class="media-footer-bar">
                                    <button type="button" class="media-full-btn" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')">
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
