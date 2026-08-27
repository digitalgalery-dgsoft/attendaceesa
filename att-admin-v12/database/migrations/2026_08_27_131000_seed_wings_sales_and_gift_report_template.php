<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan seluruh entitas PT WINGS SURYA & PT LION WINGS
        $wingsPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('name', 'LIKE', '%LION%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhere('subdomain', 'LIKE', '%wings%');
        })->get();

        if ($wingsPrincipals->isEmpty()) {
            $primaryWings = Principal::firstOrCreate(
                ['code' => 'PR-WINGS-SURYA'],
                [
                    'name' => 'PT WINGS SURYA',
                    'subdomain' => 'wings',
                    'theme_color' => '#E53935',
                    'theme_color_secondary' => '#C62828',
                    'portal_title' => 'Portal Pelaporan & Monitoring Wings Surya',
                    'is_active' => true,
                ]
            );
            $wingsPrincipals = collect([$primaryWings]);
        }

        $primaryWings = $wingsPrincipals->first();
        $allWingsIds = $wingsPrincipals->pluck('id')->toArray();

        // 2. Daftarkan Master Produk Nuvo & Wings Care
        $nuvoProducts = [
            [
                'name' => 'Nuvo Liquid Soap Kemerdekaan 450ml',
                'sku_code' => 'NV-LQ-450',
                'category' => 'Personal Wash',
                'brand' => 'NUVO',
                'price' => 25500,
                'uom' => 'Pouch',
            ],
            [
                'name' => 'Nuvo Liquid Soap Merah / Kuning / Biru 250ml',
                'sku_code' => 'NV-LQ-250',
                'category' => 'Personal Wash',
                'brand' => 'NUVO',
                'price' => 16500,
                'uom' => 'Bottle',
            ],
            [
                'name' => 'Nuvo Liquid Soap Family Pouch 825ml',
                'sku_code' => 'NV-LQ-825',
                'category' => 'Personal Wash',
                'brand' => 'NUVO',
                'price' => 42000,
                'uom' => 'Pouch',
            ],
            [
                'name' => 'Nuvo Bar Soap Classic 76g',
                'sku_code' => 'NV-BR-76',
                'category' => 'Personal Wash',
                'brand' => 'NUVO',
                'price' => 4500,
                'uom' => 'Pcs',
            ],
            [
                'name' => 'Nuvo Bar Soap Jumbo 110g',
                'sku_code' => 'NV-BR-110',
                'category' => 'Personal Wash',
                'brand' => 'NUVO',
                'price' => 6500,
                'uom' => 'Pcs',
            ],
            [
                'name' => 'Nuvo Hand Sanitizer Gel 85ml',
                'sku_code' => 'NV-HS-85',
                'category' => 'Antiseptic',
                'brand' => 'NUVO',
                'price' => 11000,
                'uom' => 'Bottle',
            ],
            [
                'name' => 'Nuvo Hand Sanitizer Spray 100ml',
                'sku_code' => 'NV-HSP-100',
                'category' => 'Antiseptic',
                'brand' => 'NUVO',
                'price' => 14500,
                'uom' => 'Bottle',
            ],
            [
                'name' => 'Nuvo Wet Wipes Antiseptik 50s',
                'sku_code' => 'NV-WW-50',
                'category' => 'Antiseptic',
                'brand' => 'NUVO',
                'price' => 17500,
                'uom' => 'Pack',
            ],
            [
                'name' => 'Mama Lemon Jeruk Nipis 230gr',
                'sku_code' => 'ML-JN-230',
                'category' => 'Dishwashing',
                'brand' => 'MAMA LEMON',
                'price' => 5500,
                'uom' => 'Pouch',
            ],
            [
                'name' => 'Mama Lemon Jeruk Nipis 680ml',
                'sku_code' => 'ML-JN-680',
                'category' => 'Dishwashing',
                'brand' => 'MAMA LEMON',
                'price' => 14000,
                'uom' => 'Pouch',
            ],
            [
                'name' => 'SoKlin Liquid Deterjen 720ml',
                'sku_code' => 'SK-LQ-720',
                'category' => 'Fabric Care',
                'brand' => 'SOKLIN',
                'price' => 19500,
                'uom' => 'Pouch',
            ],
            [
                'name' => 'Daia Deterjen Bubuk 850g',
                'sku_code' => 'DA-BB-850',
                'category' => 'Fabric Care',
                'brand' => 'DAIA',
                'price' => 18000,
                'uom' => 'Bag',
            ],
        ];

        $createdProductIds = [];
        foreach ($nuvoProducts as $pData) {
            $prod = Product::updateOrCreate(
                [
                    'principal_id' => $primaryWings->id,
                    'sku_code' => $pData['sku_code'],
                ],
                array_merge($pData, [
                    'is_active' => true,
                ])
            );
            $createdProductIds[] = $prod->id;
        }

        // 3. Buat Template Resmi: Report Penjualan & Pembagian Hadiah Nuvo
        $templateData = [
            'code' => 'RPT-WINGS-PENJUALAN-HADIAH-01',
            'title' => 'Report Penjualan & Pembagian Hadiah Nuvo (Wings Surya & Lion Wings)',
            'description' => 'Pencatatan total nominal penjualan produk Nuvo, total jumlah hadiah yang keluar, serta rincian pembagian hadiah (Jaket Bomber, Payung, Mug/Gelas, Toples/Kotak Makan, Mama Lemon 230gr, dan Hadiah Tambahan).',
            'category' => 'offtake',
            'require_gps' => true,
            'require_signature' => false,
            'is_active' => true,
            'version' => 1,
        ];

        if (Schema::hasColumn('report_templates', 'icon')) {
            $templateData['icon'] = 'gift';
        }
        if (Schema::hasColumn('report_templates', 'color')) {
            $templateData['color'] = '#E53935';
        }

        $template = ReportTemplate::updateOrCreate(
            ['code' => $templateData['code']],
            array_merge($templateData, ['principal_id' => $primaryWings->id])
        );

        // Sync relasi principal
        $template->principals()->sync($allWingsIds);

        // Sync relasi products jika tabel pivot tersedia
        if (Schema::hasTable('report_template_product')) {
            $template->products()->sync($createdProductIds);
        }

        // 4. Daftarkan Field Sesuai Format Excel Nuvo Jumputan Kemerdekaan
        $fields = [
            [
                'field_label' => 'Produk / Varian Nuvo yang Dijual',
                'field_name' => 'produk_terjual',
                'field_type' => 'dropdown',
                'options' => [
                    'Nuvo Liquid Soap Kemerdekaan 450ml',
                    'Nuvo Liquid Soap 250ml',
                    'Nuvo Liquid Soap Family 825ml',
                    'Nuvo Bar Soap Classic 76g',
                    'Nuvo Bar Soap Jumbo 110g',
                    'Nuvo Hand Sanitizer Gel 85ml',
                    'Nuvo Hand Sanitizer Spray 100ml',
                    'Nuvo Wet Wipes Antiseptik 50s',
                    'Mama Lemon Jeruk Nipis 230gr',
                    'Semua Varian Nuvo & Wings Promo',
                ],
                'placeholder' => 'Pilih varian produk...',
                'is_required' => true,
            ],
            [
                'field_label' => 'Total Penjualan Nuvo (Nominal Rupiah)',
                'field_name' => 'total_penjualan_nuvo_rp',
                'field_type' => 'currency',
                'placeholder' => 'Rp 0',
                'help_text' => 'Total omzet penjualan Nuvo hari ini dalam rupiah (in rupiah)',
                'is_required' => true,
            ],
            [
                'field_label' => 'Total Pembagian Hadiah (Total Pcs)',
                'field_name' => 'total_pembagian_hadiah',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Total seluruh pcs hadiah yang dibagikan ke konsumen (in pcs)',
                'is_required' => true,
            ],
            [
                'field_label' => 'Jumlah Hadiah: Jaket Bomber (Pcs)',
                'field_name' => 'qty_jaket_bomber',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah jaket bomber yang keluar (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Jumlah Hadiah: Payung (Pcs)',
                'field_name' => 'qty_payung',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah payung yang keluar (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Jumlah Hadiah: Mug / Gelas (Pcs)',
                'field_name' => 'qty_mug_gelas',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah mug / gelas yang keluar (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Jumlah Hadiah: Toples / Kotak Makan (Pcs)',
                'field_name' => 'qty_toples_kotak_makan',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah toples / kotak makan yang keluar (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Jumlah Hadiah: Mama Lemon 230gr (Pcs)',
                'field_name' => 'qty_mama_lemon',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah Mama Lemon 230gr yang keluar sebagai hadiah (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Hadiah Tambahan Lain (Apabila Ada)',
                'field_name' => 'hadiah_tambahan_lain',
                'field_type' => 'text',
                'placeholder' => 'Contoh: Piring Cantik, Tas Belanja, Pouch...',
                'help_text' => 'Jenis hadiah tambahan jika ada promosi ekstra di toko',
                'is_required' => false,
            ],
            [
                'field_label' => 'Jumlah Hadiah Tambahan (Pcs)',
                'field_name' => 'qty_hadiah_tambahan',
                'field_type' => 'number',
                'placeholder' => '0',
                'help_text' => 'Jumlah pcs hadiah tambahan yang keluar (isi 0 jika tidak ada)',
                'is_required' => false,
            ],
            [
                'field_label' => 'Foto Bukti Struk Kasir / Penyerahan Hadiah',
                'field_name' => 'foto_struk_hadiah',
                'field_type' => 'camera_photo',
                'help_text' => 'Wajib foto struk pembelian dan/atau penyerahan hadiah ke konsumen',
                'is_required' => true,
            ],
            [
                'field_label' => 'Catatan / Respon Konsumen & Event Penjualan',
                'field_name' => 'catatan_penjualan',
                'field_type' => 'textarea',
                'placeholder' => 'Catatan situasi penjualan, respon konsumen terhadap hadiah, atau ketersediaan stok...',
                'is_required' => false,
            ],
        ];

        foreach ($fields as $index => $field) {
            ReportFormField::updateOrCreate(
                [
                    'report_template_id' => $template->id,
                    'field_name' => $field['field_name'],
                ],
                array_merge($field, [
                    'order_index' => $index + 1,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = ReportTemplate::where('code', 'RPT-WINGS-PENJUALAN-HADIAH-01')->first();
        if ($template) {
            $template->fields()->delete();
            $template->delete();
        }
    }
};
