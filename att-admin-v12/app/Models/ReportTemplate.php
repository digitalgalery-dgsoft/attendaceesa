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
     * Pastikan Form Reporting Dulux telah menggabungkan Tinter ke Stock End dan menghapus Tinter terpisah.
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

        // 3. Pastikan Stock End Memiliki 12 Field Lengkap
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
            'White (W1)',
            'Black (B1)',
            'Yellow Oxide (Y1)',
            'Red Oxide (R1)',
            'Organic Yellow (Y2)',
            'Organic Red (R2)',
            'Blue (BL)',
            'Green (GR)',
            'Magenta (MG)',
            'Orange (OR)',
            'Violet (VT)',
            'Semua Warna Dramatone / Full Set',
        ];

        $acotoneOptions = [
            'Acotone White (AW)',
            'Acotone Black (AB)',
            'Acotone Yellow Oxide (AYO)',
            'Acotone Red Oxide (ARO)',
            'Acotone Bright Yellow (AY2)',
            'Acotone Bright Red (AR2)',
            'Acotone Blue (ABL)',
            'Acotone Green (AGR)',
            'Acotone Magenta (AMG)',
            'Acotone Orange (AOR)',
            'Acotone Violet (AVT)',
            'Acotone Transparent Red (ATR)',
            'Acotone Transparent Yellow (ATY)',
            'Semua Warna Acotone / Full Set',
        ];

        $allTinterOptions = array_merge($dramatoneOptions, $acotoneOptions);

        $fields = [
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

        ReportFormField::where('report_template_id', $stockEnd->id)->delete();

        foreach ($fields as $index => $field) {
            ReportFormField::create(array_merge($field, [
                'report_template_id' => $stockEnd->id,
                'order_index' => $index + 1,
            ]));
        }

        // 4. Pastikan SELURUH Form Template Dulux / ICI terhubung ke Principal Dulux
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

            // 5. Pastikan template non-Dulux (Mamasuka, Wings, dll) TIDAK terhubung ke Dulux
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
    }
}
