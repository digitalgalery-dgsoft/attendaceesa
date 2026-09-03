<?php

use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $oos = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if (!$oos) {
            return;
        }

        $oos->update([
            'title' => 'Laporan Out of Stock (OOS) Dulux',
            'description' => 'Pencatatan barang kosong (Out of Stock) dan ketersediaan display (OSA) di gerai Modern Trade (LSO) dan Toko Tradisional (SSO) setiap periode/minggu.',
            'category' => 'stock',
            'is_active' => true,
            'dashboard_config' => [
                'version' => 1,
                'is_custom' => true,
                'widgets' => [
                    [
                        'id' => 'kpi_total_submissions',
                        'type' => 'kpi_card',
                        'title' => 'Total Laporan Masuk',
                        'col_span' => 4,
                        'icon' => 'fa-file-invoice',
                        'color' => 'blue',
                        'dimension_field' => '_total_count',
                        'metric_field' => '_submission',
                        'aggregation' => 'COUNT',
                        'prefix' => '',
                        'suffix' => ' Laporan',
                    ],
                    [
                        'id' => 'kpi_unique_stores',
                        'type' => 'kpi_card',
                        'title' => 'Toko / Outlet Terpantau',
                        'col_span' => 4,
                        'icon' => 'fa-store',
                        'color' => 'emerald',
                        'dimension_field' => 'work_location_id',
                        'metric_field' => '_unique_store',
                        'aggregation' => 'DISTINCT_COUNT',
                        'prefix' => '',
                        'suffix' => ' Toko',
                    ],
                    [
                        'id' => 'kpi_real_oos_cases',
                        'type' => 'kpi_card',
                        'title' => 'Total Kasus OOS Riil',
                        'col_span' => 4,
                        'icon' => 'fa-triangle-exclamation',
                        'color' => 'amber',
                        'dimension_field' => 'status_ketersediaan',
                        'metric_field' => 'lama_oos_hari',
                        'aggregation' => 'COUNT',
                        'prefix' => '',
                        'suffix' => ' Kasus OOS',
                    ],
                    [
                        'id' => 'chart_daily_trend',
                        'type' => 'line_chart',
                        'title' => 'Tren Pelaporan OOS Harian',
                        'col_span' => 12,
                        'dimension_field' => '_submitted_date',
                        'metric_field' => '_count',
                        'aggregation' => 'COUNT',
                        'color' => 'blue',
                    ],
                    [
                        'id' => 'chart_channel_distribution',
                        'type' => 'donut_chart',
                        'title' => 'Distribusi Kasus per Channel Toko (LSO vs SSO)',
                        'col_span' => 4,
                        'dimension_field' => 'channel_toko',
                        'metric_field' => '_count',
                        'aggregation' => 'COUNT',
                        'color' => 'sky',
                    ],
                    [
                        'id' => 'chart_top_oos_products',
                        'type' => 'bar_chart',
                        'title' => 'Top 10 Produk Paling Sering Mengalami OOS',
                        'col_span' => 4,
                        'dimension_field' => 'produk_oos',
                        'metric_field' => '_count',
                        'aggregation' => 'COUNT',
                        'color' => 'rose',
                    ],
                    [
                        'id' => 'chart_oos_reasons',
                        'type' => 'bar_chart',
                        'title' => 'Distribusi Alasan & Penyebab OOS',
                        'col_span' => 4,
                        'dimension_field' => 'alasan_oos',
                        'metric_field' => '_count',
                        'aggregation' => 'COUNT',
                        'color' => 'indigo',
                    ],
                    [
                        'id' => 'table_submissions',
                        'type' => 'data_table',
                        'title' => 'Rincian Data Submission Laporan OOS',
                        'col_span' => 12,
                        'show_gps' => true,
                        'show_status' => true,
                        'columns' => [
                            'channel_toko',
                            'week',
                            'produk_oos',
                            'base_warna_oos',
                            'kemasan_size_oos',
                            'lama_oos_hari',
                            'saran_qty_order',
                            'alasan_oos',
                            'status_ketersediaan',
                            'account_lso',
                            'rsm_area',
                        ],
                    ],
                ],
            ],
        ]);

        $extraFields = [
            [
                'field_label' => 'Minggu / Week Pelaporan',
                'field_name' => 'week',
                'field_type' => 'number',
                'placeholder' => 'Week ke-...',
                'is_required' => false,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Status Ketersediaan Produk',
                'field_name' => 'status_ketersediaan',
                'field_type' => 'dropdown',
                'options' => ['OOS Riil', 'No OOS / Stok Lengkap'],
                'is_required' => true,
                'order_index' => 9,
            ],
            [
                'field_label' => 'Akun Modern Trade (Khusus LSO)',
                'field_name' => 'account_lso',
                'field_type' => 'text',
                'placeholder' => 'Ace Hardware, Mitra10, Depo Bangunan, dll.',
                'is_required' => false,
                'order_index' => 10,
            ],
            [
                'field_label' => 'RSM Area (Khusus SSO)',
                'field_name' => 'rsm_area',
                'field_type' => 'text',
                'placeholder' => 'Nama RSM Area',
                'is_required' => false,
                'order_index' => 11,
            ],
            [
                'field_label' => 'ID Member Toko / DERP (Khusus SSO)',
                'field_name' => 'id_member_derp',
                'field_type' => 'text',
                'placeholder' => 'ID DERP Member Toko',
                'is_required' => false,
                'order_index' => 12,
            ],
        ];

        foreach ($extraFields as $f) {
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $oos->id,
                    'field_name' => $f['field_name'],
                ],
                array_merge($f, ['report_template_id' => $oos->id])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
