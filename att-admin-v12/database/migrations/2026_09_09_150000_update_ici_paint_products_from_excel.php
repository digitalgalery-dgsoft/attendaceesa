<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Principal;
use App\Models\ReportTemplate;
use App\Models\ReportFormField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan atau Buat Principal ICI Paint
        $dulux = Principal::where("code", "PR-ICI-PAINTS")
            ->orWhere("code", "PR-DULUX")
            ->orWhere("code", "115")
            ->orWhere("name", "LIKE", "%ICI PAINTS%")
            ->orWhere("name", "LIKE", "%DULUX%")
            ->orWhere("subdomain", "dulux")
            ->first();

        if (!$dulux) {
            $dulux = Principal::create([
                "code" => "PR-ICI-PAINTS",
                "name" => "PT ICI PAINTS INDONESIA",
                "subdomain" => "dulux",
                "theme_color" => "#0F52BA",
                "theme_color_secondary" => "#0284C7",
                "portal_title" => "Portal Pelaporan & Monitoring Dulux (ICI Paints)",
                "is_active" => true,
            ]);
        }

        $companyId = DB::table("companies")->value("id");

        // 2. Hapus 5 Produk Demo Dulux lama secara permanen
        $demoSkus = [
            "DLX-WTS-WHT-25L",
            "DLX-CTL-INT-5KG",
            "DLX-ECL-ANT-25L",
            "DLX-AQS-ABU-4KG",
            "DLX-PNT-ALM-25L",
        ];

        $demoProducts = Product::whereIn("sku_code", $demoSkus)->withTrashed()->get();
        foreach ($demoProducts as $dp) {
            DB::table("report_template_product")->where("product_id", $dp->id)->delete();
            $dp->forceDelete();
        }

        // 3. Masukkan 69 Produk Resmi dari Excel List Product Dulux_Updated_Final.xlsx
        $productsData = [
            [
                "name" => "Catylac Exterior Base",
                "sku_code" => "DLX-CATYLAC-EXTERIOR-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 230000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Exterior\", \"sub_brand_2\": \"Catylac Exterior\", \"col5_name\": \"Catylac Exterior Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 230000, \"base_a_pail\": 1107000, \"base_b_tin\": null, \"base_b_galon\": 213000, \"base_b_pail\": 1020000, \"base_c_tin\": null, \"base_c_galon\": 168000, \"base_c_pail\": 835000, \"base_d_tin\": null, \"base_d_galon\": 161000, \"base_d_pail\": 797000}}",
            ],
            [
                "name" => "Catylac Glow Base",
                "sku_code" => "DLX-CATYLAC-GLOW-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 180000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Glow\", \"sub_brand_2\": \"Catylac Glow\", \"col5_name\": \"Catylac Glow Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4.5, \"pail\": 22}, \"conversion_to_liter\": 1.22, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 180000, \"base_a_pail\": 865000, \"base_b_tin\": null, \"base_b_galon\": 168000, \"base_b_pail\": 804000, \"base_c_tin\": null, \"base_c_galon\": 165000, \"base_c_pail\": 784000, \"base_d_tin\": null, \"base_d_galon\": 154000, \"base_d_pail\": 737000}}",
            ],
            [
                "name" => "Catylac Interior 2in1 Base",
                "sku_code" => "DLX-CATYLAC-INTERIOR-2IN1-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 196000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Interior 2in1\", \"sub_brand_2\": \"Catylac Interior 2in1\", \"col5_name\": \"Catylac Interior 2in1 Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 196000, \"base_a_pail\": 944000, \"base_b_tin\": null, \"base_b_galon\": 192000, \"base_b_pail\": 926000, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Interior Base",
                "sku_code" => "DLX-CATYLAC-INTERIOR-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 160000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Interior\", \"sub_brand_2\": \"Catylac Interior\", \"col5_name\": \"Catylac Interior Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 160000, \"base_a_pail\": 761000, \"base_b_tin\": null, \"base_b_galon\": 155000, \"base_b_pail\": 744000, \"base_c_tin\": null, \"base_c_galon\": 142000, \"base_c_pail\": 680000, \"base_d_tin\": null, \"base_d_galon\": 133000, \"base_d_pail\": 641000}}",
            ],
            [
                "name" => "Catylac Smart Choice Exterior Base",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-EXTERIOR-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Smart Choice Exterior\", \"sub_brand_2\": \"Catylac Smart Choice Exterior\", \"col5_name\": \"Catylac Smart Choice Exterior Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Smart Choice Interior Base",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-INTERIOR-BASE",
                "brand" => "Catylac",
                "category" => "Catylac Base",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac Base\", \"sub_brand\": \"Catylac Smart Choice Interior\", \"sub_brand_2\": \"Catylac Smart Choice Interior\", \"col5_name\": \"Catylac Smart Choice Interior Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Ceiling",
                "sku_code" => "DLX-CATYLAC-CEILING",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 150000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Ceiling\", \"sub_brand_2\": \"Catylac Ceiling\", \"col5_name\": \"Catylac Ceiling\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 150000, \"rm_pail\": 734000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Exterior",
                "sku_code" => "DLX-CATYLAC-EXTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 242000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Exterior\", \"sub_brand_2\": \"Catylac Exterior\", \"col5_name\": \"Catylac Exterior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 242000, \"rm_pail\": 1175000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Glow",
                "sku_code" => "DLX-CATYLAC-GLOW",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 207000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Glow\", \"sub_brand_2\": \"Catylac Glow\", \"col5_name\": \"Catylac Glow\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4.5, \"pail\": 22}, \"conversion_to_liter\": 1.22, \"prices\": {\"rm_tin\": null, \"rm_galon\": 207000, \"rm_pail\": 1000000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Hi-Gloss",
                "sku_code" => "DLX-CATYLAC-HI-GLOSS",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 65591.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Hi-Gloss\", \"sub_brand_2\": \"Catylac Hi-Gloss\", \"col5_name\": \"Catylac Hi-Gloss\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 0.9, \"galon\": null, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": 65591, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Interior",
                "sku_code" => "DLX-CATYLAC-INTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 184000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Interior\", \"sub_brand_2\": \"Catylac Interior\", \"col5_name\": \"Catylac Interior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 184000, \"rm_pail\": 892000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Interior 2in1",
                "sku_code" => "DLX-CATYLAC-INTERIOR-2IN1",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 221000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Interior 2in1\", \"sub_brand_2\": \"Catylac Interior 2in1\", \"col5_name\": \"Catylac Interior 2in1\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 221000, \"rm_pail\": 1075000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Plamur",
                "sku_code" => "DLX-CATYLAC-PLAMUR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 70000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Plamur\", \"sub_brand_2\": \"Catylac Plamur\", \"col5_name\": \"Catylac Plamur\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 70000, \"rm_pail\": 340000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Primer Eksterior",
                "sku_code" => "DLX-CATYLAC-PRIMER-EKSTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 155000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Exterior Primer\", \"sub_brand_2\": \"Catylac Primer Eksterior\", \"col5_name\": \"Catylac Primer Eksterior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4, \"pail\": 21}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 155000, \"rm_pail\": 783000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Primer Interior",
                "sku_code" => "DLX-CATYLAC-PRIMER-INTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 132000.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Interior Primer\", \"sub_brand_2\": \"Catylac Primer Interior\", \"col5_name\": \"Catylac Primer Interior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4, \"pail\": 21}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 132000, \"rm_pail\": 669000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Primer Kayu dan Besi",
                "sku_code" => "DLX-CATYLAC-PRIMER-KAYU-DAN-BESI",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Ltr",
                "price" => 58527.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Primer Kayu dan Besi\", \"sub_brand_2\": \"Catylac Primer Kayu dan Besi\", \"col5_name\": \"Catylac Primer Kayu dan Besi\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": null, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": 58527, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Smart Choice Exterior",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-EXTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Smart Choice Exterior\", \"sub_brand_2\": \"Catylac Smart Choice Exterior\", \"col5_name\": \"Catylac Smart Choice Exterior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Smart Choice Exterior Primer",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-EXTERIOR-PRIMER",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Smart Choice Exterior Primer\", \"sub_brand_2\": \"Catylac Smart Choice Exterior Primer\", \"col5_name\": \"Catylac Smart Choice Exterior Primer\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Smart Choice Interior",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-INTERIOR",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Smart Choice Interior\", \"sub_brand_2\": \"Catylac Smart Choice Interior\", \"col5_name\": \"Catylac Smart Choice Interior\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Catylac Smart Choice Interior Primer",
                "sku_code" => "DLX-CATYLAC-SMART-CHOICE-INTERIOR-PRIMER",
                "brand" => "Catylac",
                "category" => "Catylac RM",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Catylac\", \"brand_rm_base\": \"Catylac RM\", \"sub_brand\": \"Catylac Smart Choice Interior Primer\", \"sub_brand_2\": \"Catylac Smart Choice Interior Primer\", \"col5_name\": \"Catylac Smart Choice Interior Primer\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Ambiance Base",
                "sku_code" => "DLX-DULUX-AMBIANCE-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 347000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Ambiance\", \"sub_brand_2\": \"Ambiance\", \"col5_name\": \"Ambiance Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 347000, \"base_a_pail\": 2477000, \"base_b_tin\": null, \"base_b_galon\": 303000, \"base_b_pail\": 2097000, \"base_c_tin\": null, \"base_c_galon\": 272000, \"base_c_pail\": 1841000, \"base_d_tin\": null, \"base_d_galon\": 259000, \"base_d_pail\": 1827000}}",
            ],
            [
                "name" => "Dulux Ambiance Diamond Glow Base",
                "sku_code" => "DLX-DULUX-AMBIANCE-DIAMOND-GLOW-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 369000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Ambiance\", \"sub_brand_2\": \"Ambiance Diamond Glow\", \"col5_name\": \"Ambiance Diamond Glow Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 369000, \"base_a_pail\": 2544000, \"base_b_tin\": null, \"base_b_galon\": 322000, \"base_b_pail\": 2197000, \"base_c_tin\": null, \"base_c_galon\": 284000, \"base_c_pail\": 1928000, \"base_d_tin\": null, \"base_d_galon\": 273000, \"base_d_pail\": 1914000}}",
            ],
            [
                "name" => "Dulux Aquashield Base",
                "sku_code" => "DLX-DULUX-AQUASHIELD-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Kg",
                "price" => 278000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Aquashield\", \"sub_brand_2\": \"Aquashield\", \"col5_name\": \"Aquashield Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 1, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.27, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": 74000, \"base_a_galon\": 278000, \"base_a_pail\": 1214000, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": 68000, \"base_d_galon\": 252000, \"base_d_pail\": 1105000}}",
            ],
            [
                "name" => "Dulux Aquashield Max Base",
                "sku_code" => "DLX-DULUX-AQUASHIELD-MAX-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Kg",
                "price" => 297000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Aquashield\", \"sub_brand_2\": \"Aquashield Max\", \"col5_name\": \"Aquashield Max Base\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 1, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.27, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": 78000, \"base_a_galon\": 297000, \"base_a_pail\": 1597000, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": 72000, \"base_d_galon\": 270000, \"base_d_pail\": 1182000}}",
            ],
            [
                "name" => "Dulux Easy Clean Anti - Viral Base",
                "sku_code" => "DLX-DULUX-EASY-CLEAN-ANTI-VIRAL-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 315000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Easy Clean\", \"sub_brand_2\": \"Easy Clean\", \"col5_name\": \"Easy Clean Anti - Viral Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 315000, \"base_a_pail\": 2242000, \"base_b_tin\": null, \"base_b_galon\": 263000, \"base_b_pail\": 1879000, \"base_c_tin\": null, \"base_c_galon\": 240000, \"base_c_pail\": 1653000, \"base_d_tin\": null, \"base_d_galon\": 227000, \"base_d_pail\": 1611000}}",
            ],
            [
                "name" => "Dulux Easy Clean Base",
                "sku_code" => "DLX-DULUX-EASY-CLEAN-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Easy Clean\", \"sub_brand_2\": \"Easy Clean\", \"col5_name\": \"Easy Clean Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Pearl Glo Base",
                "sku_code" => "DLX-DULUX-PEARL-GLO-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Others\", \"sub_brand_2\": \"Pearl Glo\", \"col5_name\": \"Pearl Glo Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Pentalite Antibac Base",
                "sku_code" => "DLX-DULUX-PENTALITE-ANTIBAC-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 256000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Pentalite\", \"sub_brand_2\": \"Pentalite\", \"col5_name\": \"Pentalite Antibac Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 256000, \"base_a_pail\": 1837000, \"base_b_tin\": null, \"base_b_galon\": 217000, \"base_b_pail\": 1570000, \"base_c_tin\": null, \"base_c_galon\": 192000, \"base_c_pail\": 1320000, \"base_d_tin\": null, \"base_d_galon\": 179000, \"base_d_pail\": 1214000}}",
            ],
            [
                "name" => "Dulux Pentalite Base",
                "sku_code" => "DLX-DULUX-PENTALITE-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Pentalite\", \"sub_brand_2\": \"Pentalite\", \"col5_name\": \"Pentalite Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Powerflexx Base",
                "sku_code" => "DLX-DULUX-POWERFLEXX-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Powerflexx\", \"sub_brand_2\": \"Powerflexx\", \"col5_name\": \"Powerflexx Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Powerflexx Next Gen Base",
                "sku_code" => "DLX-DULUX-POWERFLEXX-NEXT-GEN-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 470000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Powerflexx\", \"sub_brand_2\": \"Powerflexx Next Gen\", \"col5_name\": \"Powerflexx Next Gen Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 470000, \"base_a_pail\": 3266000, \"base_b_tin\": null, \"base_b_galon\": 409000, \"base_b_pail\": 2950000, \"base_c_tin\": null, \"base_c_galon\": 386000, \"base_c_pail\": 2656000, \"base_d_tin\": null, \"base_d_galon\": 367000, \"base_d_pail\": 2549000}}",
            ],
            [
                "name" => "Dulux V-Gloss Base",
                "sku_code" => "DLX-DULUX-V-GLOSS-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"V-Gloss\", \"sub_brand_2\": \"V-Gloss\", \"col5_name\": \"V-Gloss Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": 2.4, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux V-Gloss High Gloss Base",
                "sku_code" => "DLX-DULUX-V-GLOSS-HIGH-GLOSS-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 73000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"V-Gloss High Gloss\", \"sub_brand_2\": \"V-Gloss High Gloss\", \"col5_name\": \"V-Gloss High Gloss Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": 2.4, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": 73000, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": 73000, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": 73000, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": 73000, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Base",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield\", \"col5_name\": \"Weathershield Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Core Dualshield Base",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-CORE-DUALSHIELD-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 416000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield Core Dualshield\", \"col5_name\": \"Weathershield Core Dualshield Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 416000, \"base_a_pail\": 2972000, \"base_b_tin\": null, \"base_b_galon\": 371000, \"base_b_pail\": 2649000, \"base_c_tin\": null, \"base_c_galon\": 345000, \"base_c_pail\": 2350000, \"base_d_tin\": null, \"base_d_galon\": 321000, \"base_d_pail\": 2220000}}",
            ],
            [
                "name" => "Dulux Weathershield Dirt Resistance Base",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-DIRT-RESISTANCE-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 437000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield Dirt Resistance\", \"col5_name\": \"Weathershield Dirt Resistance Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 437000, \"base_a_pail\": 3121000, \"base_b_tin\": null, \"base_b_galon\": 390000, \"base_b_pail\": 2781000, \"base_c_tin\": null, \"base_c_galon\": 362000, \"base_c_pail\": 2468000, \"base_d_tin\": null, \"base_d_galon\": 337000, \"base_d_pail\": 2331000}}",
            ],
            [
                "name" => "Dulux Weathershield Flash Base",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-FLASH-BASE",
                "brand" => "Dulux",
                "category" => "Dulux Base",
                "uom" => "Ltr",
                "price" => 443000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux Base\", \"sub_brand\": \"Weathershield Flash\", \"sub_brand_2\": \"Weathershield Flash\", \"col5_name\": \"Weathershield Flash Base\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": 443000, \"base_a_pail\": 3050000, \"base_b_tin\": null, \"base_b_galon\": 389000, \"base_b_pail\": 2806000, \"base_c_tin\": null, \"base_c_galon\": 377000, \"base_c_pail\": 2601000, \"base_d_tin\": null, \"base_d_galon\": 359000, \"base_d_pail\": 2493000}}",
            ],
            [
                "name" => "Dulux Alkali Killer",
                "sku_code" => "DLX-DULUX-ALKALI-KILLER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 236000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Alkali Killer\", \"sub_brand_2\": \"Alkali Killer\", \"col5_name\": \"Alkali Killer\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 236000, \"rm_pail\": 1631000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Alkali Resisting Primer",
                "sku_code" => "DLX-DULUX-ALKALI-RESISTING-PRIMER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 167000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Alkali Resisting Primer\", \"sub_brand_2\": \"Alkali Resisting Primer\", \"col5_name\": \"Alkali Resisting Primer\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 167000, \"rm_pail\": 1355000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Ambiance",
                "sku_code" => "DLX-DULUX-AMBIANCE",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 377000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Ambiance\", \"sub_brand_2\": \"Ambiance\", \"col5_name\": \"Ambiance\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 377000, \"rm_pail\": 2645000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Ambiance Diamond Glow",
                "sku_code" => "DLX-DULUX-AMBIANCE-DIAMOND-GLOW",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 399000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Ambiance\", \"sub_brand_2\": \"Ambiance Diamond Glow\", \"col5_name\": \"Ambiance Diamond Glow\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 399000, \"rm_pail\": 2824000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Aquashield",
                "sku_code" => "DLX-DULUX-AQUASHIELD",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 234000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Aquashield\", \"sub_brand_2\": \"Aquashield\", \"col5_name\": \"Aquashield\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 1, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.27, \"prices\": {\"rm_tin\": 81000, \"rm_galon\": 234000, \"rm_pail\": 1346000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Aquashield 2K",
                "sku_code" => "DLX-DULUX-AQUASHIELD-2K",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 143000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Aquashield\", \"sub_brand_2\": \"Aquashield 2K\", \"col5_name\": \"Aquashield 2K\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 143000, \"rm_pail\": 480000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Aquashield Max",
                "sku_code" => "DLX-DULUX-AQUASHIELD-MAX",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 331000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Aquashield\", \"sub_brand_2\": \"Aquashield Max\", \"col5_name\": \"Aquashield Max\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 1, \"galon\": 4, \"pail\": 20}, \"conversion_to_liter\": 1.27, \"prices\": {\"rm_tin\": 87000, \"rm_galon\": 331000, \"rm_pail\": 1437000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Ceiling",
                "sku_code" => "DLX-DULUX-CEILING",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 228000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Ceiling\", \"sub_brand_2\": \"Ceiling\", \"col5_name\": \"Ceiling\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 5, \"pail\": 25}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 228000, \"rm_pail\": 1102000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Easy Clean",
                "sku_code" => "DLX-DULUX-EASY-CLEAN",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Easy Clean\", \"sub_brand_2\": \"Easy Clean\", \"col5_name\": \"Easy Clean\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Easy Clean Anti - Viral",
                "sku_code" => "DLX-DULUX-EASY-CLEAN-ANTI-VIRAL",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 339000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Easy Clean\", \"sub_brand_2\": \"Easy Clean\", \"col5_name\": \"Easy Clean Anti - Viral\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 339000, \"rm_pail\": 2300000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Hammerite - DTG",
                "sku_code" => "DLX-DULUX-HAMMERITE-DTG",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Hammerite - DTG\", \"sub_brand_2\": \"Hammerite - DTG\", \"col5_name\": \"Hammerite - DTG\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.25, \"galon\": 0.75, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Hammerite Thinner",
                "sku_code" => "DLX-DULUX-HAMMERITE-THINNER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Hammerite Thinner\", \"sub_brand_2\": \"Hammerite Thinner\", \"col5_name\": \"Hammerite Thinner\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.25, \"galon\": 0.75, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Pentalite",
                "sku_code" => "DLX-DULUX-PENTALITE",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Pentalite\", \"sub_brand_2\": \"Pentalite\", \"col5_name\": \"Pentalite\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Pentalite Antibac",
                "sku_code" => "DLX-DULUX-PENTALITE-ANTIBAC",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 272000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Pentalite\", \"sub_brand_2\": \"Pentalite\", \"col5_name\": \"Pentalite Antibac\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 272000, \"rm_pail\": 2064000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Pentalite Light & Space",
                "sku_code" => "DLX-DULUX-PENTALITE-LIGHT-SPACE",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 215000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Others\", \"sub_brand_2\": \"Pentalite Light & Space\", \"col5_name\": \"Pentalite Light & Space\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 215000, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Powerflexx",
                "sku_code" => "DLX-DULUX-POWERFLEXX",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Powerflexx\", \"sub_brand_2\": \"Powerflexx\", \"col5_name\": \"Powerflexx\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Powerflexx Next Gen",
                "sku_code" => "DLX-DULUX-POWERFLEXX-NEXT-GEN",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 480000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Powerflexx\", \"sub_brand_2\": \"Powerflexx Next Gen\", \"col5_name\": \"Powerflexx Next Gen\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 480000, \"rm_pail\": 3436000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Thinner",
                "sku_code" => "DLX-DULUX-THINNER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 41000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Others\", \"sub_brand_2\": \"Thinner\", \"col5_name\": \"Thinner\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 1, \"galon\": null, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": 41000, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux V-Gloss",
                "sku_code" => "DLX-DULUX-V-GLOSS",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"V-Gloss\", \"sub_brand_2\": \"V-Gloss\", \"col5_name\": \"V-Gloss\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": 2.4, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux V-Gloss Doff",
                "sku_code" => "DLX-DULUX-V-GLOSS-DOFF",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"V-Gloss Doff\", \"sub_brand_2\": \"V-Gloss Doff\", \"col5_name\": \"V-Gloss Doff\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": 2.4, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux V-Gloss High Gloss",
                "sku_code" => "DLX-DULUX-V-GLOSS-HIGH-GLOSS",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"V-Gloss High Gloss\", \"sub_brand_2\": \"V-Gloss High Gloss\", \"col5_name\": \"V-Gloss High Gloss\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.8, \"galon\": 2.4, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Wallfiller",
                "sku_code" => "DLX-DULUX-WALLFILLER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 108150.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Others\", \"sub_brand_2\": \"Wallfiller\", \"col5_name\": \"Wallfiller\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 3.5, \"pail\": null}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 108150, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield\", \"col5_name\": \"Weathershield\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Core Dualshield",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-CORE-DUALSHIELD",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 453000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield Core Dualshield\", \"col5_name\": \"Weathershield Core Dualshield\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 453000, \"rm_pail\": 3245000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Dirt Resistance",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-DIRT-RESISTANCE",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 460000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield\", \"sub_brand_2\": \"Weathershield Dirt Resistance\", \"col5_name\": \"Weathershield Dirt Resistance\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 460000, \"rm_pail\": 3337000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Flash",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-FLASH",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 469000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Flash\", \"sub_brand_2\": \"Weathershield Flash\", \"col5_name\": \"Weathershield Flash\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 469000, \"rm_pail\": 3429000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Gloss",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-GLOSS",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 0.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Gloss\", \"sub_brand_2\": \"Weathershield Gloss\", \"col5_name\": \"Weathershield Gloss\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": 0.9, \"galon\": null, \"pail\": null}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Power Sealer",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-POWER-SEALER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 218300.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Power Sealer\", \"sub_brand_2\": \"Weathershield Power Sealer\", \"col5_name\": \"Weathershield Power Sealer\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 218300, \"rm_pail\": 1619000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Primer",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-PRIMER",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 208000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Primer\", \"sub_brand_2\": \"Weathershield Primer\", \"col5_name\": \"Weathershield Primer\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 208000, \"rm_pail\": 1542000, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Putty",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-PUTTY",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Kg",
                "price" => 166000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Putty\", \"sub_brand_2\": \"Weathershield Putty\", \"col5_name\": \"Weathershield Putty\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": 3.5, \"galon\": null, \"pail\": null}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": 166000, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Dulux Weathershield Roof Paint",
                "sku_code" => "DLX-DULUX-WEATHERSHIELD-ROOF-PAINT",
                "brand" => "Dulux",
                "category" => "Dulux RM",
                "uom" => "Ltr",
                "price" => 273000.0,
                "description" => "{\"brand\": \"Dulux\", \"brand_rm_base\": \"Dulux RM\", \"sub_brand\": \"Weathershield Roof Paint\", \"sub_brand_2\": \"Weathershield Roof Paint\", \"col5_name\": \"Weathershield Roof Paint\", \"uom\": \"Ltr\", \"packaging_sizes\": {\"tin\": null, \"galon\": 2.5, \"pail\": 20}, \"conversion_to_liter\": null, \"prices\": {\"rm_tin\": null, \"rm_galon\": 273000, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
            [
                "name" => "Maxilite",
                "sku_code" => "DLX-MAXILITE",
                "brand" => "Maxilite",
                "category" => "Maxilite",
                "uom" => "Kg",
                "price" => 0.0,
                "description" => "{\"brand\": \"Maxilite\", \"brand_rm_base\": \"Maxilite\", \"sub_brand\": \"Others\", \"sub_brand_2\": \"Maxilite\", \"col5_name\": \"Maxilite\", \"uom\": \"Kg\", \"packaging_sizes\": {\"tin\": null, \"galon\": 4.5, \"pail\": 18.5}, \"conversion_to_liter\": 1.4, \"prices\": {\"rm_tin\": null, \"rm_galon\": null, \"rm_pail\": null, \"base_a_tin\": null, \"base_a_galon\": null, \"base_a_pail\": null, \"base_b_tin\": null, \"base_b_galon\": null, \"base_b_pail\": null, \"base_c_tin\": null, \"base_c_galon\": null, \"base_c_pail\": null, \"base_d_tin\": null, \"base_d_galon\": null, \"base_d_pail\": null}}",
            ],
        ];

        $newProductIds = [];
        $newProductNames = [];

        foreach ($productsData as $p) {
            $prod = Product::withTrashed()->updateOrCreate(
                ["sku_code" => $p["sku_code"]],
                [
                    "principal_id" => $dulux->id,
                    "company_id" => $companyId,
                    "name" => $p["name"],
                    "brand" => $p["brand"],
                    "category" => $p["category"],
                    "uom" => $p["uom"],
                    "price" => $p["price"],
                    "min_stock" => 0,
                    "description" => Product::formatDescriptionText($p["description"]),
                    "is_active" => true,
                    "deleted_at" => null,
                ]
            );

            $newProductIds[] = $prod->id;
            $newProductNames[] = $prod->name;
        }

        // 4. Tautkan ke seluruh template laporan Dulux aktif via pivot table
        $duluxTemplates = ReportTemplate::where("code", "LIKE", "RPT-DULUX-%")->get();
        foreach ($duluxTemplates as $template) {
            // Kecuali Daily Maintenance (Daily Maintenance murni mengikat ke mesin)
            if ($template->code === "RPT-DULUX-DAILY-MAINTENANCE") {
                DB::table("report_template_product")->where("report_template_id", $template->id)->delete();
                continue;
            }

            // Sync 69 produk baru ke template laporan
            $template->products()->sync($newProductIds);
        }

        // 5. Perbarui options field dropdown produk pada template Dulux
        $productSelectFields = ReportFormField::whereHas("template", function ($q) {
            $q->where("code", "LIKE", "RPT-DULUX-%")
              ->where("code", "!=", "RPT-DULUX-DAILY-MAINTENANCE");
        })->where(function ($q) {
            $q->where("field_type", "product_select")
              ->orWhereIn("field_name", ["produk_oos", "produk_stock_end", "produk_dulux_cbp"]);
        })->get();

        foreach ($productSelectFields as $field) {
            $field->options = $newProductNames;
            $field->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};
