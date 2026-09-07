<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'schedule_type' => 'string',
        'target_count' => 'integer',
        'require_gps' => 'boolean',
        'require_signature' => 'boolean',
        'is_active' => 'boolean',
        'min_photos' => 'integer',
        'max_photos' => 'integer',
        'version' => 'integer',
        'report_days' => 'array',
        'dashboard_config' => 'array',
    ];

    /**
     * Hitung total target pengisian laporan dalam rentang periode cut-off tertentu.
     */
    public function calculateCutoffTarget(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int
    {
        $scheduleType = strtolower($this->schedule_type ?? 'daily');
        $targetCount = max(1, (int) ($this->target_count ?? 1));
        $reportDays = is_array($this->report_days) ? array_map('strtolower', $this->report_days) : [];

        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
        ];

        if ($scheduleType === 'monthly') {
            return $targetCount;
        }

        if ($scheduleType === 'weekly') {
            // Hitung estimasi jumlah minggu dalam rentang cut-off (biasanya 4 s/d 5 minggu)
            $totalDays = max(1, $startDate->diffInDays($endDate->copy()->addDay()));
            $weeks = max(1, (int) round($totalDays / 7));
            return $targetCount * $weeks;
        }

        // Default: Daily (Harian)
        if (empty($reportDays)) {
            // Setiap hari dalam rentang cut-off
            return max(1, $startDate->diffInDays($endDate->copy()->addDay()));
        }

        // Hitung hari yang sesuai dengan pilihan report_days
        $matchingDays = 0;
        $curr = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        while ($curr->lte($end)) {
            $dayName = $dayMap[$curr->dayOfWeekIso] ?? '';
            if (in_array($dayName, $reportDays)) {
                $matchingDays++;
            }
            $curr->addDay();
        }

        return max(1, $matchingDays);
    }

    /**
     * Cek apakah hari ini termasuk hari aktif pengisian form.
     */
    public function isScheduledForDate(\Carbon\Carbon $date): bool
    {
        $reportDays = is_array($this->report_days) ? array_map('strtolower', $this->report_days) : [];
        if (empty($reportDays)) {
            return true;
        }

        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
        ];

        $dayName = $dayMap[$date->dayOfWeekIso] ?? '';
        return in_array($dayName, $reportDays);
    }

    /**
     * Get default dashboard configuration for this template
     */
    public function getDefaultDashboardConfig(): array
    {
        return [
            'version' => 1,
            'is_custom' => false,
            'widgets' => [
                [
                    'id' => 'kpi_total_submissions',
                    'type' => 'kpi_card',
                    'title' => 'Total Laporan Masuk',
                    'col_span' => 6,
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
                    'title' => 'Outlet / Toko Terjangkau',
                    'col_span' => 6,
                    'icon' => 'fa-store',
                    'color' => 'emerald',
                    'dimension_field' => 'work_location_id',
                    'metric_field' => '_unique_store',
                    'aggregation' => 'DISTINCT_COUNT',
                    'prefix' => '',
                    'suffix' => ' Toko',
                ],
                [
                    'id' => 'chart_daily_trend',
                    'type' => 'line_chart',
                    'title' => 'Tren Laporan Harian Periode Ini',
                    'col_span' => 12,
                    'dimension_field' => '_submitted_date',
                    'metric_field' => '_count',
                    'aggregation' => 'COUNT',
                    'color' => 'blue',
                ],
                [
                    'id' => 'table_submissions',
                    'type' => 'data_table',
                    'title' => 'Rincian Data Submission Laporan',
                    'col_span' => 12,
                    'show_gps' => true,
                    'show_status' => true,
                    'columns' => [],
                ],
            ],
        ];
    }

    /**
     * Get resolved dashboard configuration (custom or default fallback)
     */
    public function getResolvedDashboardConfigAttribute(): array
    {
        if (!empty($this->dashboard_config) && is_array($this->dashboard_config) && !empty($this->dashboard_config['widgets'])) {
            $config = $this->dashboard_config;
            $config['is_custom'] = true;
            return $config;
        }

        return $this->getDefaultDashboardConfig();
    }

    protected static function booted()
    {
        static::saving(function ($template) {
            // Jika principal_id kosong tapi ada relasi principals di request
            if (empty($template->principal_id) && request()->has('principals')) {
                $principals = request()->input('principals', []);
                if (is_array($principals) && !empty($principals)) {
                    $template->principal_id = $principals[0];
                }
            }
        });
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function principals(): BelongsToMany
    {
        return $this->belongsToMany(Principal::class, 'report_template_principal')->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'report_template_product')->withTimestamps();
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'report_template_position')->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'report_template_employee')->withTimestamps();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ReportFormField::class)->orderBy('order_index');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ReportSubmission::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReportTemplateAssignment::class);
    }

    /**
     * Pastikan Form Reporting Dulux telah disesuaikan dan disinkronkan secara konsisten di seluruh server.
     */
    public static function syncDuluxMergedStockEnd(): void
    {
        // 1. Dapatkan Principal Dulux
        $duluxPrincipals = Principal::where('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%ICI%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->get();

        $allDuluxIds = $duluxPrincipals->pluck('id')->toArray();
        $primaryDulux = $duluxPrincipals->first() ?? Principal::first();

        if (!$primaryDulux) {
            return;
        }

        // 2. HAPUS TOTAL Laporan Tinter Terpisah (RPT-DULUX-TINTER-LSO)
        $tinterTemplates = static::where('code', 'RPT-DULUX-TINTER-LSO')
            ->orWhere('title', 'LIKE', '%Laporan Tinter%')
            ->get();

        foreach ($tinterTemplates as $tinter) {
            ReportFormField::where('report_template_id', $tinter->id)->delete();
            $tinter->principals()->detach();
            $tinter->assignments()->delete();
            $tinter->delete();
        }

        // 3. Stock End (12 Field Lengkap)
        $stockEnd = static::where('code', 'RPT-DULUX-STOCK-END')->first();
        if (!$stockEnd) {
            $stockEnd = static::create([
                'code' => 'RPT-DULUX-STOCK-END',
                'principal_id' => $primaryDulux->id,
                'title' => 'Laporan Stock End (Stock Opname Bulanan) & Tinter Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux & Catylac serta ketersediaan pasta tinter mesin tinting (Dramatone & Acotone) di toko.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
                'report_days' => [],
            ]);
        } else {
            $stockEnd->update([
                'title' => 'Laporan Stock End (Stock Opname Bulanan) & Tinter Dulux',
                'description' => 'Pencatatan sisa stok fisik akhir bulan seluruh SKU Dulux & Catylac serta ketersediaan pasta tinter mesin tinting (Dramatone & Acotone) di toko.',
                'category' => 'stock',
                'require_gps' => true,
                'require_signature' => false,
                'is_active' => true,
                'version' => 2,
            ]);
        }
        if (!empty($allDuluxIds)) {
            $stockEnd->principals()->sync($allDuluxIds);
        }

        $dramatoneOptions = [
            'White (W1)', 'Black (B1)', 'Yellow Oxide (Y1)', 'Red Oxide (R1)', 'Organic Yellow (Y2)',
            'Organic Red (R2)', 'Blue (BL)', 'Green (GR)', 'Magenta (MG)', 'Orange (OR)', 'Violet (VT)',
            'Semua Warna Dramatone / Full Set',
        ];

        $acotoneOptions = [
            'Acotone White (AW)', 'Acotone Black (AB)', 'Acotone Yellow Oxide (AYO)', 'Acotone Red Oxide (ARO)',
            'Acotone Bright Yellow (AY2)', 'Acotone Bright Red (AR2)', 'Acotone Blue (ABL)', 'Acotone Green (AGR)',
            'Acotone Magenta (AMG)', 'Acotone Orange (AOR)', 'Acotone Violet (AVT)', 'Acotone Transparent Red (ATR)',
            'Acotone Transparent Yellow (ATY)', 'Semua Warna Acotone / Full Set',
        ];

        $allTinterOptions = array_merge($dramatoneOptions, $acotoneOptions);

        $stockEndFields = [
            ['field_label' => 'Pilih Produk Dulux / Catylac yang Dicek', 'field_name' => 'produk_stock_end', 'field_type' => 'product_select', 'is_required' => true],
            ['field_label' => 'Base / Tipe Warna', 'field_name' => 'base_warna', 'field_type' => 'dropdown', 'options' => ['Base A (Putih/Light)', 'Base B (Medium)', 'Base C (Dark)', 'Base D (Clear/Deep)', 'Ready Mix (Warna Jadi Pabrik)', 'Cat Dasar Primer'], 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Galon (Qty)', 'field_name' => 'stok_qty_galon', 'field_type' => 'number', 'placeholder' => 'Jumlah galon', 'is_required' => true],
            ['field_label' => 'Stok Fisik Kemasan Pail (Qty)', 'field_name' => 'stok_qty_pail', 'field_type' => 'number', 'placeholder' => 'Jumlah pail', 'is_required' => true],
            ['field_label' => 'Estimasi Total Volume Stok di Toko (Liter)', 'field_name' => 'total_volume_stok_liter', 'field_type' => 'number', 'placeholder' => 'Total volume liter', 'is_required' => true],
            ['field_label' => 'Kategori Tinter / Mesin Tinting', 'field_name' => 'kategori_tinter', 'field_type' => 'dropdown', 'options' => ['Dramatone', 'Acotone', 'Tidak Ada Mesin / Non-Tinting'], 'is_required' => true],
            ['field_label' => 'Tipe Tinter / Warna Pasta Pewarna', 'field_name' => 'tipe_tinter_warna', 'field_type' => 'dropdown', 'options' => $allTinterOptions, 'is_required' => true],
            ['field_label' => 'Kuantiti / Jumlah Kaleng Tinta Tinter', 'field_name' => 'qty_kaleng_tinta', 'field_type' => 'number', 'placeholder' => 'Jumlah kaleng tinter', 'is_required' => false],
            ['field_label' => 'Status Ketersediaan Tinter di Toko', 'field_name' => 'status_ketersediaan_tinter', 'field_type' => 'radio', 'options' => ['Stok Aman (Siap Oplos)', 'Stok Menipis (Perlu Order Ulang)', 'Stok Habis (Mesin Tidak Bisa Oplos)', 'Tidak Ada Mesin'], 'is_required' => true],
            ['field_label' => 'Status Akses Pengecekan Gudang Toko', 'field_name' => 'status_akses_gudang', 'field_type' => 'radio', 'options' => ['Full Access (Bisa Cek Rak & Gudang Toko Bebas)', 'Half Access (Hanya Cek Rak Depan Toko)', 'No Access (Toko Menolak Cek Fisik / Data Estimasi)'], 'is_required' => true],
            ['field_label' => 'Foto Fisik Rak Display, Tumpukan Stok Gudang & Mesin Tinter', 'field_name' => 'foto_stok_gudang', 'field_type' => 'multi_photo', 'is_required' => true],
            ['field_label' => 'Keterangan / Kendala Stok & Tinter Toko', 'field_name' => 'keterangan_stok_toko', 'field_type' => 'textarea', 'placeholder' => 'Catatan status stok lambat laku (slow moving), kelebihan stok, atau request restock tinter...', 'is_required' => false],
        ];

        foreach ($stockEndFields as $index => $field) {
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $stockEnd->id,
                    'field_name' => $field['field_name'],
                ],
                array_merge($field, [
                    'order_index' => $index + 1,
                ])
            );
        }

        // 4. Laporan CBP (Consumer Buying Price) - Dinamis Kompetitor Tin/Galon/Pail, Promo Nominal/Persen, Foto Dihapus
        $cbpTemplate = static::where('code', 'RPT-DULUX-CBP-PRICING')->first();
        if ($cbpTemplate) {
            $cbpTemplate->update([
                'title' => 'Laporan CBP (Consumer Buying Price) & Cek Harga Dulux',
                'description' => 'Monitoring harga beli konsumen (CBP) produk Dulux, Catylac, serta harga brand & subbrand kompetitor (Tin, Galon, Pail) dan promo toko.',
                'category' => 'pricing',
                'is_active' => true,
            ]);

            $cbpFields = [
                ['field_label' => 'Pilih Produk Dulux yang Dicek Harganya', 'field_name' => 'produk_dulux_cbp', 'field_type' => 'product_select', 'is_required' => true],
                ['field_label' => 'Kemasan Produk Dulux', 'field_name' => 'kemasan_produk', 'field_type' => 'dropdown', 'options' => ['1 Liter / 1 Kg (Small Tin)', '2.5 Liter / 4 Kg / 5 Kg (Galon)', '20 Liter / 25 Kg (Pail Besar)'], 'is_required' => true],
                ['field_label' => 'Harga Jual Toko ke Konsumen Dulux (CBP Rp)', 'field_name' => 'harga_cbp_dulux_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                ['field_label' => 'Merk Kompetitor Sejenis di Toko', 'field_name' => 'merk_kompetitor', 'field_type' => 'dropdown', 'options' => ['JOTUN', 'NIPPON PAINT', 'AVIAN / NO DROP / LENKOTE', 'MOWILEX', 'PROPAN', 'KANSAI / DANAPAINT', 'PACIFIC PAINT', 'MERK LAINNYA'], 'is_required' => true],
                ['field_label' => 'Nama Subbrand Kompetitor yang Dicek', 'field_name' => 'subbrand_kompetitor', 'field_type' => 'text', 'placeholder' => 'Contoh: Majestic / Spotless / Sunguard / Weathercoat / Cendana', 'is_required' => true],
                ['field_label' => 'Harga Jual Kompetitor Kemasan Tin / Kaleng 1L/1Kg (Rp)', 'field_name' => 'harga_kompetitor_tin_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
                ['field_label' => 'Harga Jual Kompetitor Kemasan Galon 2.5L/4-5Kg (Rp)', 'field_name' => 'harga_kompetitor_galon_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
                ['field_label' => 'Harga Jual Kompetitor Kemasan Pail 20L/25Kg (Rp)', 'field_name' => 'harga_kompetitor_pail_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
                ['field_label' => 'Diskon / Potongan Harga Promo Toko (Nominal Rp)', 'field_name' => 'diskon_promo_nominal_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => false],
                ['field_label' => 'Diskon / Potongan Harga Promo Toko (Persen %)', 'field_name' => 'diskon_promo_persen', 'field_type' => 'number', 'placeholder' => 'Contoh: 10 (%)', 'is_required' => false],
                ['field_label' => 'Keterangan Program Promo / Bundling Toko', 'field_name' => 'keterangan_promo_toko', 'field_type' => 'text', 'placeholder' => 'Contoh: Cashback kupon toko, bundling kuas cat, promo akhir pekan...', 'is_required' => false],
            ];

            foreach ($cbpFields as $index => $field) {
                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $cbpTemplate->id,
                        'field_name' => $field['field_name'],
                    ],
                    array_merge($field, [
                        'order_index' => $index + 1,
                    ])
                );
            }
        }

        // 5. GABUNGKAN Laporan OOS (SSO & LSO) -> Satu Form RPT-DULUX-OOS-SSO
        // Nonaktifkan RPT-DULUX-OOS-LSO
        $oosLso = static::where('code', 'RPT-DULUX-OOS-LSO')->first();
        if ($oosLso) {
            $oosLso->update(['is_active' => false]);
        }

        $oosTemplate = static::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if ($oosTemplate) {
            $oosTemplate->update([
                'title' => 'Laporan Out of Stock (OOS) Dulux',
                'description' => 'Pencatatan barang kosong (Out of Stock) di toko Specialist Traditional (SSO) maupun Modern Trade (LSO).',
                'category' => 'stock',
                'is_active' => true,
                'report_days' => [],
            ]);

            $readyMixColors = [
                'Bukan Ready Mix (Base Oplos)',
                'Brilliant White (1001)',
                'White (1000)',
                'Black / Hitam (1002)',
                'Barley White (1003)',
                'Off White',
                'Grey / Abu-abu',
                'Cream / Vanilla',
                'Tropical Green / Hijau',
                'Sky Blue / Biru',
                'Sunshine / Kuning',
                'Signal Red / Merah',
                'Mocha / Coklat',
                'Warna Ready Mix Lainnya',
            ];

            $oosReasons = [
                '1. Sudah buka PO namun belum ada pengiriman ke toko',
                '2. Sudah buka PO namun kendala stock di distributor/pabrik',
                '3. Kendala pembayaran toko (limit kredit / kiriman diblokir)',
                '4. Barang sedang dalam proses pengiriman ke toko',
                '5. Stok DC / Hub Modern Trade Kosong',
                '6. PO Toko Belum Diterbitkan Buyer / PIC',
                '7. Toko belum bersedia reorder / menunggu omset',
            ];

            $oosFields = [
                ['field_label' => 'Tipe Gerai / Channel Toko', 'field_name' => 'channel_toko', 'field_type' => 'dropdown', 'options' => ['Specialist Traditional Store (SSO)', 'Modern Outlet / Toko Modern (LSO)'], 'is_required' => true],
                ['field_label' => 'Pilih Produk Dulux yang Mengalami Out of Stock (OOS)', 'field_name' => 'produk_oos', 'field_type' => 'product_select', 'is_required' => true],
                ['field_label' => 'Kemasan / Size yang Kosong', 'field_name' => 'kemasan_size_oos', 'field_type' => 'dropdown', 'options' => ['Small Tin (1L / 1Kg)', 'Galon (2.5L / 4-5Kg)', 'Pail Besar (20L / 25Kg)'], 'is_required' => true],
                ['field_label' => 'Base / Kategori Warna yang Kosong', 'field_name' => 'base_warna_oos', 'field_type' => 'dropdown', 'options' => ['Base A', 'Base B', 'Base C', 'Base D', 'Ready Mix / Warna Jadi', 'Alkali Primer / Cat Dasar'], 'is_required' => true],
                ['field_label' => 'Pilihan Warna Ready Mix (Jika Warna Jadi Kosong)', 'field_name' => 'warna_ready_mix_oos', 'field_type' => 'dropdown', 'options' => $readyMixColors, 'is_required' => true],
                ['field_label' => 'Lama Kondisi Barang Kosong (Jumlah Hari)', 'field_name' => 'lama_oos_hari', 'field_type' => 'number', 'placeholder' => 'Contoh: 7 (hari)', 'is_required' => true],
                ['field_label' => 'Saran Kuantiti Order ke Toko (Qty Kemasan)', 'field_name' => 'saran_qty_order', 'field_type' => 'number', 'placeholder' => 'Saran kuantiti order', 'is_required' => false],
                ['field_label' => 'Penyebab / Alasan Out of Stock (OOS)', 'field_name' => 'alasan_oos', 'field_type' => 'dropdown', 'options' => $oosReasons, 'is_required' => true],
            ];

            foreach ($oosFields as $index => $field) {
                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $oosTemplate->id,
                        'field_name' => $field['field_name'],
                    ],
                    array_merge($field, [
                        'order_index' => $index + 1,
                    ])
                );
            }
        }

        // 6. Laporan Data Pelanggan & Konsumen Dulux
        $pelangganTemplate = static::where('code', 'RPT-DULUX-DATABASE-PELANGGAN')->first();
        if ($pelangganTemplate) {
            $pelangganTemplate->update([
                'title' => 'Laporan Data Pelanggan & Konsumen Dulux',
                'description' => 'Pendataan profil konsumen pembeli cat di toko, segmentasi pelanggan, brand preference & switching, serta estimasi nilai transaksi.',
                'category' => 'general',
                'is_active' => true,
            ]);

            $brandSoughtOptions = [
                'Dulux',
                'Catylac',
                'Jotun',
                'Nippon Paint / Vinilex',
                'Avian / Avitex / No Drop',
                'Mowilex',
                'Propan',
                'Danapaint / Kansai',
                'Pacific Paint',
                'Merk Lainnya',
            ];

            $brandBoughtOptions = [
                'Dulux (Pentalite / Weathershield / EasyClean / Ambiance)',
                'Catylac (Interior / Exterior / Plamur)',
                'Aquashield (Pelapis Anti Bocor)',
                'Dulux Catylac (Gabungan)',
                'Jotun',
                'Nippon Paint',
                'Avian / No Drop / Lenkote',
                'Mowilex',
                'Propan',
                'Tidak Jadi Beli Cat',
                'Lainnya',
            ];

            $pelangganFields = [
                ['field_label' => 'Nama Lengkap Pelanggan', 'field_name' => 'nama_pelanggan', 'field_type' => 'text', 'placeholder' => 'Nama konsumen / pembeli', 'is_required' => true],
                ['field_label' => 'Nomor HP / WhatsApp Pelanggan', 'field_name' => 'no_hp_pelanggan', 'field_type' => 'text', 'placeholder' => '08xxxxxxxxxx / (62) 8xx', 'is_required' => true],
                ['field_label' => 'Alamat / Domisili Pelanggan', 'field_name' => 'alamat_pelanggan', 'field_type' => 'text', 'placeholder' => 'Alamat atau area domisili konsumen', 'is_required' => false],
                ['field_label' => 'Tipe / Kategori Pelanggan', 'field_name' => 'tipe_pelanggan', 'field_type' => 'radio', 'options' => ['Pemilik Rumah', 'Tukang Cat & Bangunan', 'Kontraktor', 'Mitra Dulux'], 'is_required' => true],
                ['field_label' => 'Tujuan Datang ke Toko', 'field_name' => 'tujuan_ke_toko', 'field_type' => 'dropdown', 'options' => ['Membeli Cat', 'Membeli Bahan Bangunan Lainnya', 'Konsultasi / Tanya Warna', 'Komplain', 'Lainnya'], 'is_required' => true],
                ['field_label' => 'Brand Cat yang Awalnya Dicari / Ditanyakan', 'field_name' => 'brand_dicari', 'field_type' => 'dropdown', 'options' => $brandSoughtOptions, 'is_required' => true],
                ['field_label' => 'Brand Cat yang Akhirnya Dibeli', 'field_name' => 'brand_dibeli', 'field_type' => 'dropdown', 'options' => $brandBoughtOptions, 'is_required' => true],
                ['field_label' => 'Alasan Konsumen Memilih Brand Tersebut', 'field_name' => 'alasan_pilih_brand', 'field_type' => 'dropdown', 'options' => ['Rekomendasi DC', 'Kualitasnya baik', 'Harga Terjangkau', 'Merk terkenal', 'Rekomendasi Painter/Kontraktor', 'Rekomendasi Toko', 'Promosi', 'Iklan'], 'is_required' => true],
                ['field_label' => 'Tipe Pekerjaan Pengecatan', 'field_name' => 'tipe_pengecatan', 'field_type' => 'radio', 'options' => ['Pengecatan Baru', 'Pengecatan Ulang'], 'is_required' => true],
                ['field_label' => 'Apakah Memerlukan Preview Warna Visualizer?', 'field_name' => 'memerlukan_preview', 'field_type' => 'radio', 'options' => ['Ya', 'Tidak'], 'is_required' => true],
                ['field_label' => 'Estimasi Total Nilai Pembelian (Rupiah)', 'field_name' => 'value_pembelian_rp', 'field_type' => 'currency', 'placeholder' => 'Rp 0', 'is_required' => true],
                ['field_label' => 'Program Mitra Dulux (Painter Loyalty)', 'field_name' => 'painter_loyalty', 'field_type' => 'radio', 'options' => ['Saya bersedia menerima informasi mengenai program Mitra Dulux', 'Tidak Bersedia'], 'is_required' => false],
                ['field_label' => 'Catatan Khusus / Keterangan', 'field_name' => 'keterangan', 'field_type' => 'textarea', 'placeholder' => 'Catatan tambahan interaksi atau preferensi warna konsumen...', 'is_required' => false],
                ['field_label' => 'Foto Interaksi / Nota 1', 'field_name' => 'foto_1', 'field_type' => 'camera_photo', 'is_required' => false],
                ['field_label' => 'Foto Interaksi / Nota 2', 'field_name' => 'foto_2', 'field_type' => 'camera_photo', 'is_required' => false],
                ['field_label' => 'Foto Interaksi / Nota 3', 'field_name' => 'foto_3', 'field_type' => 'camera_photo', 'is_required' => false],
            ];

            foreach ($pelangganFields as $index => $field) {
                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $pelangganTemplate->id,
                        'field_name' => $field['field_name'],
                    ],
                    array_merge($field, [
                        'order_index' => $index + 1,
                    ])
                );
            }
        }

        // 7. Laporan Daily Maintenance - Tipe Mesin & No Seri Mesin Disave per Toko
        $maintTemplate = static::where('code', 'RPT-DULUX-DAILY-MAINTENANCE')->first();
        if ($maintTemplate) {
            $maintTemplate->update([
                'title' => 'Laporan Daily Maintenance POST & Mesin Tinting Dulux',
                'description' => 'Kartu harian pemeriksaan & perawatan mesin tinting (POST Maintenance), nozzle cleaning, kalibrasi, dan nomor mesin toko.',
                'category' => 'display',
                'is_active' => true,
            ]);

            $maintFields = [
                ['field_label' => 'Tipe Mesin Tinting POST di Toko', 'field_name' => 'tipe_mesin_post', 'field_type' => 'dropdown', 'options' => ['Mesin D200 (Automatic Tinting)', 'Mesin Discovery (Automatic Tinting)', 'Mesin XProtint (Automatic Tinting)', 'Mesin Element 2', 'Mesin Manual Dispenser', 'Toko Tidak Memiliki Mesin Tinting'], 'is_required' => true],
                ['field_label' => 'Nomor Seri / No Mesin POST Dulux', 'field_name' => 'no_mesin_post', 'field_type' => 'text', 'placeholder' => 'Contoh: POST-2022-JKT-042', 'is_required' => false],
                ['field_label' => 'Status Pemeriksaan Kebersihan Nozzle & Brush Cleaning', 'field_name' => 'status_nozzle_cleaning', 'field_type' => 'radio', 'options' => ['Nozzle Bersih & Sudah Dilakukan Brush Cleaning', 'Nozzle Tersumbat (Butuh Pembersihan Ekstra / Air Hangat)', 'Nozzle Rusak / Butuh Service Teknisi'], 'is_required' => true],
                ['field_label' => 'Status Sirkulasi & Agitasi Pasta Tinter', 'field_name' => 'status_sirkulasi_tinter', 'field_type' => 'radio', 'options' => ['Sirkulasi & Pengadukan Tinter Berjalan Normal', 'Motor Agitasi Berisik / Butuh Pelumasan', 'Tinta Mengendap / Tidak Berputar'], 'is_required' => true],
                ['field_label' => 'Status Software Tinting Komputer & Database Formula Warna', 'field_name' => 'status_software_komputer', 'field_type' => 'radio', 'options' => ['Software Normal & Database Formula Warna Terupdate', 'Software Error / Butuh Update Formula', 'Komputer / Monitor Mati'], 'is_required' => true],
                ['field_label' => 'Status Partisipasi Program Mix2Win Toko', 'field_name' => 'status_program_mix2win', 'field_type' => 'radio', 'options' => ['Program Mix2Win Aktif & Kupon Tercetak Normal', 'Program Mix2Win Belum Diaktifkan Toko', 'Kendala Printer / Sambungan Internet Mix2Win'], 'is_required' => true],
                ['field_label' => 'Foto Proses Brush Cleaning / Pembersihan Nozzle Mesin', 'field_name' => 'foto_brush_cleaning', 'field_type' => 'camera_photo', 'is_required' => true],
                ['field_label' => 'Foto Mesin Tinting & Area Oplos Toko', 'field_name' => 'foto_mesin_tinting', 'field_type' => 'camera_photo', 'is_required' => true],
                ['field_label' => 'Kesimpulan Kondisi Mesin & Rekomendasi Maintenance', 'field_name' => 'kesimpulan_maintenance', 'field_type' => 'textarea', 'placeholder' => 'Tuliskan kendala teknis atau pasta yang perlu di-restock teknisi...', 'is_required' => false],
            ];

            foreach ($maintFields as $index => $field) {
                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $maintTemplate->id,
                        'field_name' => $field['field_name'],
                    ],
                    array_merge($field, [
                        'order_index' => $index + 1,
                    ])
                );
            }
        }

        // 8. Pastikan SELURUH Form Template Dulux / ICI terhubung ke Principal Dulux
        if (!empty($allDuluxIds)) {
            $allDuluxTemplates = static::where('code', 'LIKE', '%DULUX%')
                ->orWhere('code', 'LIKE', '%ICI%')
                ->orWhere('title', 'LIKE', '%Dulux%')
                ->orWhere('title', 'LIKE', '%ICI%')
                ->get();

            foreach ($allDuluxTemplates as $tpl) {
                if (empty($tpl->principal_id) && $primaryDulux) {
                    $tpl->principal_id = $primaryDulux->id;
                    $tpl->save();
                }
                $tpl->principals()->syncWithoutDetaching($allDuluxIds);
            }

            // 9. Pastikan template non-Dulux (Mamasuka, Wings, dll) TIDAK terhubung ke Dulux
            $nonDuluxTemplates = static::where('code', 'LIKE', '%MAMASUKA%')
                ->orWhere('code', 'LIKE', '%DAESANG%')
                ->orWhere('code', 'LIKE', '%WINGS%')
                ->orWhere('code', 'LIKE', '%FONTERRA%')
                ->orWhere('code', 'LIKE', '%SIDO%')
                ->orWhere('title', 'LIKE', '%Mamasuka%')
                ->get();

            foreach ($nonDuluxTemplates as $nd) {
                $nd->principals()->detach($allDuluxIds);
                if (in_array($nd->principal_id, $allDuluxIds)) {
                    $correctPrincipal = null;
                    if (str_contains($nd->code, 'MAMASUKA') || str_contains($nd->code, 'DAESANG') || str_contains($nd->title, 'Mamasuka')) {
                        $correctPrincipal = Principal::where('name', 'LIKE', '%MAMASUKA%')->orWhere('name', 'LIKE', '%DAESANG%')->first();
                    } elseif (str_contains($nd->code, 'WINGS')) {
                        $correctPrincipal = Principal::where('name', 'LIKE', '%WINGS%')->first();
                    } elseif (str_contains($nd->code, 'FONTERRA')) {
                        $correctPrincipal = Principal::where('name', 'LIKE', '%FONTERRA%')->first();
                    } elseif (str_contains($nd->code, 'SIDO')) {
                        $correctPrincipal = Principal::where('name', 'LIKE', '%SIDO%')->first();
                    }
                    $nd->principal_id = $correctPrincipal ? $correctPrincipal->id : null;
                    $nd->save();
                    if ($correctPrincipal) {
                        $nd->principals()->syncWithoutDetaching([$correctPrincipal->id]);
                    }
                }
            }
        }

        // 10. Re-link foreign key report_form_field_id pada report_submission_values yang null/yatim
        try {
            $orphans = \App\Models\ReportSubmissionValue::where(function ($q) {
                $q->whereNull('report_form_field_id')
                  ->orWhere('report_form_field_id', 0);
            })->with('submission')->limit(500)->get();

            foreach ($orphans as $val) {
                if ($val->submission && $val->submission->report_template_id) {
                    $matchedField = \App\Models\ReportFormField::where('report_template_id', $val->submission->report_template_id)
                        ->where('field_name', $val->field_name)
                        ->first();
                    if ($matchedField) {
                        $val->update(['report_form_field_id' => $matchedField->id]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Re-linking orphaned report submission values: " . $e->getMessage());
        }
    }
}
