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
        // 1. OFFTAKE TEMPLATE (RPT-DULUX-OFFTAKE-01) - Mark calculated fields as readonly
        $offtake = ReportTemplate::where('code', 'RPT-DULUX-OFFTAKE-01')->first();
        if ($offtake) {
            $readonlyFields = ['volume_galon_l', 'volume_pail_l', 'total_volume_unit', 'total_volume_liter'];
            ReportFormField::where('report_template_id', $offtake->id)
                ->whereIn('field_name', $readonlyFields)
                ->update(['is_readonly' => true]);

            // Ensure quantity and volume fields have numeric types
            ReportFormField::where('report_template_id', $offtake->id)
                ->whereIn('field_name', ['qty_galon', 'qty_pail', 'total_volume_unit'])
                ->update(['field_type' => 'number']);

            ReportFormField::where('report_template_id', $offtake->id)
                ->whereIn('field_name', ['volume_galon_l', 'volume_pail_l', 'total_volume_liter'])
                ->update(['field_type' => 'number']);

            ReportFormField::where('report_template_id', $offtake->id)
                ->where('field_name', 'total_nilai_sales_rp')
                ->update(['field_type' => 'currency']);
        }

        // 2. STOCK END TEMPLATE (RPT-DULUX-STOCK-END) - Mark total volume as readonly
        $stockEnd = ReportTemplate::where('code', 'RPT-DULUX-STOCK-END')->first();
        if ($stockEnd) {
            ReportFormField::where('report_template_id', $stockEnd->id)
                ->whereIn('field_name', ['total_volume_stok_liter', 'volume_liter'])
                ->update(['is_readonly' => true, 'field_type' => 'number']);

            ReportFormField::where('report_template_id', $stockEnd->id)
                ->whereIn('field_name', ['stok_qty_galon', 'stok_qty_pail', 'kuantiti_galon', 'kuantiti_pail', 'qty_kaleng_tinta'])
                ->update(['field_type' => 'number']);

            ReportFormField::where('report_template_id', $stockEnd->id)
                ->whereIn('field_name', ['produk_stock_end', 'produk'])
                ->update(['field_type' => 'product_select']);
        }

        // 3. TRAFIK PEMBELI TEMPLATE (RPT-DULUX-TRAFIK-PEMBELI) - Mark market share as readonly
        $trafik = ReportTemplate::where('code', 'RPT-DULUX-TRAFIK-PEMBELI')->first();
        if ($trafik) {
            ReportFormField::where('report_template_id', $trafik->id)
                ->where('field_name', 'estimasi_market_share_persen')
                ->update(['is_readonly' => true, 'field_type' => 'number']);

            ReportFormField::where('report_template_id', $trafik->id)
                ->whereIn('field_name', ['jml_customer_datang', 'jml_customer_beli_cat', 'jml_customer_beli_dulux'])
                ->update(['field_type' => 'number']);
        }

        // 4. CBP PRICING TEMPLATE (RPT-DULUX-CBP-PRICING) - Ensure currency fields
        $cbp = ReportTemplate::where('code', 'RPT-DULUX-CBP-PRICING')->first();
        if ($cbp) {
            $currencyFields = [
                'harga_tin_rp', 'harga_terendah_tin_rp',
                'harga_galon_rp', 'harga_terendah_galon_rp',
                'harga_pail_rp', 'harga_terendah_pail_rp',
                'harga_cbp_dulux_rp', 'harga_kompetitor_jotun_rp',
                'harga_kompetitor_nippon_rp', 'harga_kompetitor_avian_rp',
                'harga_kompetitor_mowilex_rp'
            ];
            ReportFormField::where('report_template_id', $cbp->id)
                ->whereIn('field_name', $currencyFields)
                ->update(['field_type' => 'currency']);
        }

        // 5. DATABASE PELANGGAN (RPT-DULUX-DATABASE-PELANGGAN) - Ensure currency field
        $custDb = ReportTemplate::where('code', 'RPT-DULUX-DATABASE-PELANGGAN')->first();
        if ($custDb) {
            ReportFormField::where('report_template_id', $custDb->id)
                ->where('field_name', 'value_pembelian_rp')
                ->update(['field_type' => 'currency']);
        }

        // 6. REGISTRASI MITRA (RPT-DULUX-REGISTRASI-MITRA) - Ensure number fields
        $mitra = ReportTemplate::where('code', 'RPT-DULUX-REGISTRASI-MITRA')->first();
        if ($mitra) {
            ReportFormField::where('report_template_id', $mitra->id)
                ->whereIn('field_name', ['jumlah_tukang_cat', 'luas_bidang_pengecatan'])
                ->update(['field_type' => 'number']);

            ReportFormField::where('report_template_id', $mitra->id)
                ->where('field_name', 'tanda_tangan_mitra_dulux')
                ->update(['field_type' => 'signature']);
        }

        // 7. OOS TEMPLATE (RPT-DULUX-OOS-SSO) - Ensure product_select and number
        $oos = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if ($oos) {
            ReportFormField::where('report_template_id', $oos->id)
                ->whereIn('field_name', ['produk_oos', 'produk_oos_sso', 'produk'])
                ->update(['field_type' => 'product_select']);

            ReportFormField::where('report_template_id', $oos->id)
                ->whereIn('field_name', ['lama_oos_hari', 'saran_qty_order', 'week'])
                ->update(['field_type' => 'number']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
