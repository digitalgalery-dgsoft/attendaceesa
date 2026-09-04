<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $template = ReportTemplate::where('code', 'RPT-DULUX-DATABASE-PELANGGAN')->first();
        if (!$template) {
            return;
        }

        $template->update([
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

        $fields = [
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

        ReportFormField::where('report_template_id', $template->id)->delete();
        foreach ($fields as $index => $field) {
            ReportFormField::create(array_merge($field, [
                'report_template_id' => $template->id,
                'order_index' => $index + 1,
            ]));
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
