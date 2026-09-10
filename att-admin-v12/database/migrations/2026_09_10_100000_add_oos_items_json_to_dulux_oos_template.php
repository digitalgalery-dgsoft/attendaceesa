<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $oosTemplate = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if (!$oosTemplate) {
            $oosTemplate = ReportTemplate::where('code', 'LIKE', '%OOS%')->first();
        }

        if ($oosTemplate) {
            // 1. Tambah field tipe_laporan_oos (no_oos vs oos)
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $oosTemplate->id,
                    'field_name' => 'tipe_laporan_oos',
                ],
                [
                    'field_label' => 'Tipe Laporan OOS',
                    'field_type' => 'radio',
                    'options' => ['OOS', 'No OOS (Stok Lengkap)'],
                    'is_required' => false,
                    'order_index' => 0,
                    'default_value' => 'OOS',
                ]
            );

            // 2. Tambah field oos_items_json untuk menampung rincian multi-produk keranjang OOS
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $oosTemplate->id,
                    'field_name' => 'oos_items_json',
                ],
                [
                    'field_label' => 'Rincian Produk OOS (JSON)',
                    'field_type' => 'textarea',
                    'is_required' => false,
                    'order_index' => 99,
                    'placeholder' => 'Data terstruktur produk OOS terkonsolidasi',
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oosTemplate = ReportTemplate::where('code', 'RPT-DULUX-OOS-SSO')->first();
        if ($oosTemplate) {
            ReportFormField::where('report_template_id', $oosTemplate->id)
                ->whereIn('field_name', ['tipe_laporan_oos', 'oos_items_json'])
                ->delete();
        }
    }
};
