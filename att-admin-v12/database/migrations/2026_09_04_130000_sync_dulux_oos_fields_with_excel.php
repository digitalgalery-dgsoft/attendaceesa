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
            'title' => 'Laporan Out of Stock (OOS) Dulux & Catylac',
            'description' => 'Pencatatan ketersediaan barang dan monitoring barang kosong (Out of Stock) di gerai Modern Trade (LSO) dan Toko Tradisional (SSO).',
            'category' => 'stock',
            'require_gps' => true,
            'is_active' => true,
        ]);

        $products = [
            'No OOS',
            'Alkali Killer', 'Alkali Resisting Primer', 'Ambiance', 'Ambiance Base',
            'Ambiance Diamond Glow', 'Ambiance Diamond Glow Base', 'Aquashield', 'Aquashield 2K',
            'Aquashield Base', 'Aquashield Max', 'Aquashield Max Base', 'Catylac Ceiling',
            'Catylac Exterior', 'Catylac Exterior Base', 'Catylac Glow', 'Catylac Glow Base',
            'Catylac Hi-Gloss', 'Catylac Interior', 'Catylac Interior 2in1',
            'Catylac Interior 2in1 Base', 'Catylac Interior Base', 'Catylac Plamur',
            'Catylac Primer Eksterior', 'Catylac Primer Interior', 'Catylac Smart Choice Exterior',
            'Catylac Smart Choice Exterior Primer', 'Catylac Smart Choice Interior',
            'Catylac Smart Choice Interior Primer', 'Ceiling', 'Easy Clean',
            'Easy Clean Anti - Viral', 'Easy Clean Anti - Viral Base', 'Easy Clean Base',
            'Hammerite - DTR', 'Hammerite Thinner', 'Pearl Glo', 'Pearl Glo Base',
            'Pentalite', 'Pentalite Antibac', 'Pentalite Antibac Base', 'Pentalite Base',
            'Pentalite Light & Space', 'Powerflexx', 'Powerflexx Base', 'Powerflexx Next Gen',
            'Powerflexx Next Gen Base', 'Tinter', 'V-Gloss', 'V-Gloss Base', 'V-Gloss Doff',
            'V-Gloss High', 'V-Gloss High Gloss', 'Wallfiller', 'Weathershield', 'Weathershield Base',
            'Weathershield Core Dualshield', 'Weathershield Core Dualshield Base',
            'Weathershield Dirt Resistance', 'Weathershield Dirt Resistance Base',
            'Weathershield Flash', 'Weathershield Flash Base', 'Weathershield Gloss',
            'Weathershield Power Sealer', 'Weathershield Primer', 'Weathershield Putty',
            'Weathershield Roof Paint',
            'DS XDN COLOURANT BLACK 0.94L', 'DS XDN COLOURANT BLUE 0.94L', 'DS XDN COLOURANT FAST FAST RED 0.94L',
            'DS XDN COLOURANT GR1 0.94L', 'DS XDN COLOURANT LIGHT FAST YELLOW 0.94L', 'DS XDN COLOURANT NO1 0.94L',
            'DS XDN COLOURANT WHITE 0.94L', 'DS XDN COLOURANT XR1 0.94L', 'DS XDN COLOURANT XY1 0.94L'
        ];

        $reasons = [
            '1. Sudah buka PO namun belum ada pengiriman ke toko',
            '2. Sudah buka PO namun kendala stock di distributor',
            '3. Kendala pembayaran (kiriman barang diblokir)',
            '4. Barang sedang dalam proses pengiriman ke toko',
            '5. Adanya pembelian dalam jumlah besar (borongan) sehingga menyebabkan OOS',
            '6. Other / Lainnya',
            '7. No OOS / Stok Lengkap'
        ];

        $fields = [
            [
                'field_label' => 'Tanggal Monitoring OOS',
                'field_name' => 'tanggal_oos',
                'field_type' => 'date',
                'is_required' => true,
                'order_index' => 1,
            ],
            [
                'field_label' => 'Minggu Ke (Week)',
                'field_name' => 'week',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 27, 28, 29, 30',
                'is_required' => true,
                'order_index' => 2,
            ],
            [
                'field_label' => 'Tipe Toko / Channel',
                'field_name' => 'channel',
                'field_type' => 'dropdown',
                'options' => ['SSO (Traditional Trade / Retail)', 'LSO (Modern Trade / Key Account)'],
                'is_required' => true,
                'order_index' => 3,
            ],
            [
                'field_label' => 'Key Account (Khusus Modern Trade)',
                'field_name' => 'account',
                'field_type' => 'dropdown',
                'options' => ['Non Modern Trade / SSO', 'CKDB', 'Mitra 10', 'ACE Hardware', 'Depo Bangunan', 'Mega Depo', 'Lainnya'],
                'is_required' => false,
                'order_index' => 4,
            ],
            [
                'field_label' => 'Nama Produk yang Kosong / OOS',
                'field_name' => 'produk',
                'field_type' => 'dropdown',
                'options' => $products,
                'is_required' => true,
                'order_index' => 5,
            ],
            [
                'field_label' => 'Base / Tipe Warna',
                'field_name' => 'base_color',
                'field_type' => 'text',
                'placeholder' => 'A / B / C / D / BRILLIANT WHITE / WHITE / Ready Mix',
                'is_required' => false,
                'order_index' => 6,
            ],
            [
                'field_label' => 'Ukuran Kemasan / Size',
                'field_name' => 'kemasan_size',
                'field_type' => 'dropdown',
                'options' => ['Galon', 'Pail', 'Tin (0.94L / 1L)', 'Semua Kemasan'],
                'is_required' => false,
                'order_index' => 7,
            ],
            [
                'field_label' => 'Lama Kondisi OOS (Jumlah Hari)',
                'field_name' => 'lama_oos_hari',
                'field_type' => 'number',
                'placeholder' => 'Contoh: 3 (hari)',
                'is_required' => false,
                'order_index' => 8,
            ],
            [
                'field_label' => 'Saran Kuantitas Order (Qty Kaleng)',
                'field_name' => 'saran_qty_order',
                'field_type' => 'number',
                'placeholder' => 'Saran kuantiti order toko',
                'is_required' => false,
                'order_index' => 9,
            ],
            [
                'field_label' => 'Penyebab / Alasan Out of Stock (OOS)',
                'field_name' => 'alasan_oos',
                'field_type' => 'dropdown',
                'options' => $reasons,
                'is_required' => true,
                'order_index' => 10,
            ],
            [
                'field_label' => 'Foto Bukti Fisik / Rak Display Toko',
                'field_name' => 'foto_oos',
                'field_type' => 'image',
                'is_required' => false,
                'order_index' => 11,
            ],
            [
                'field_label' => 'Tindak Lanjut & Rencana Order Toko',
                'field_name' => 'catatan_tindak_lanjut',
                'field_type' => 'textarea',
                'placeholder' => 'Catatan konfirmasi ke PIC toko mengenai jadwal PO...',
                'is_required' => false,
                'order_index' => 12,
            ],
        ];

        ReportFormField::where('report_template_id', $oos->id)->delete();

        foreach ($fields as $f) {
            ReportFormField::create(array_merge($f, [
                'report_template_id' => $oos->id,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
