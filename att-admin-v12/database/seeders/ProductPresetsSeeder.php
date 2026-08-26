<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Principal;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductPresetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        $companyId = $company?->id;

        // 1. WINGS SURYA & LION WINGS
        $wingsSurya = Principal::where('code', 'PR-WINGS-SURYA')
            ->orWhere('name', 'LIKE', '%WINGS SURYA%')
            ->first();

        $lionWings = Principal::where('code', 'PR-LION-WINGS')
            ->orWhere('name', 'LIKE', '%LION WINGS%')
            ->first();

        if ($wingsSurya) {
            $wingsProducts = [
                [
                    'name' => 'SoKlin Liquid Detergent Antibac 720ml',
                    'sku_code' => 'WNG-SKL-LIQ-720',
                    'barcode' => '8998866101102',
                    'category' => 'Care / Fabric Detergent',
                    'brand' => 'SoKlin',
                    'price' => 19500,
                    'uom' => 'Pouch',
                    'description' => 'Deterjen cair konsentrat dengan formula antibakteri 99.9%.',
                ],
                [
                    'name' => 'SoKlin Softergent Pink Bunga 770g',
                    'sku_code' => 'WNG-SKL-SFT-770',
                    'barcode' => '8998866101157',
                    'category' => 'Care / Fabric Detergent',
                    'brand' => 'SoKlin',
                    'price' => 20500,
                    'uom' => 'Pack',
                    'description' => 'Deterjen bubuk plus pelembut pakaian aroma wangi bunga pink.',
                ],
                [
                    'name' => 'Daia Deterjen Bubuk Bunga 850g',
                    'sku_code' => 'WNG-DIA-BNG-850',
                    'barcode' => '8998866102201',
                    'category' => 'Care / Fabric Detergent',
                    'brand' => 'Daia',
                    'price' => 21000,
                    'uom' => 'Pack',
                    'description' => 'Deterjen bubuk dengan busa melimpah dan aroma segar.',
                ],
                [
                    'name' => 'Daia Deterjen Bubuk Putih Bersih 850g',
                    'sku_code' => 'WNG-DIA-PTH-850',
                    'barcode' => '8998866102218',
                    'category' => 'Care / Fabric Detergent',
                    'brand' => 'Daia',
                    'price' => 21000,
                    'uom' => 'Pack',
                    'description' => 'Deterjen bubuk untuk pakaian putih cemerlang.',
                ],
                [
                    'name' => 'Mie Sedaap Goreng Original 90g',
                    'sku_code' => 'WNG-MSD-GRG-90',
                    'barcode' => '8998866200010',
                    'category' => 'Food & Beverage',
                    'brand' => 'Mie Sedaap',
                    'price' => 3200,
                    'uom' => 'Bks',
                    'description' => 'Mie instan goreng dengan kriuk-kriuk bawang gurih renyah.',
                ],
                [
                    'name' => 'Mie Sedaap Soto Madura 75g',
                    'sku_code' => 'WNG-MSD-STO-75',
                    'barcode' => '8998866200027',
                    'category' => 'Food & Beverage',
                    'brand' => 'Mie Sedaap',
                    'price' => 3200,
                    'uom' => 'Bks',
                    'description' => 'Mie kuah rasa soto khas dengan aroma jeruk nipis segar.',
                ],
                [
                    'name' => 'Floridina Orange Real Pulpy 350ml',
                    'sku_code' => 'WNG-FLR-ORG-350',
                    'barcode' => '8998866300055',
                    'category' => 'Food & Beverage',
                    'brand' => 'Floridina',
                    'price' => 3500,
                    'uom' => 'Btl',
                    'description' => 'Minuman jus jeruk Florida dengan bulir jeruk asli menyegarkan.',
                ],
                [
                    'name' => 'Top Coffee Kopi Susu Gula Aren 9x25g',
                    'sku_code' => 'WNG-TOP-ARN-25G',
                    'barcode' => '8998866300123',
                    'category' => 'Food & Beverage',
                    'brand' => 'Top Coffee',
                    'price' => 14000,
                    'uom' => 'Bag',
                    'description' => 'Kopi bubuk instan perpaduan kopi robusta, susu lembut, dan gula aren asli.',
                ],
                [
                    'name' => 'JasJus Rasa Jeruk Segar 10x8g',
                    'sku_code' => 'WNG-JSJ-JRK-8G',
                    'barcode' => '8998866300208',
                    'category' => 'Food & Beverage',
                    'brand' => 'JasJus',
                    'price' => 5500,
                    'uom' => 'Renceng',
                    'description' => 'Minuman serbuk rasa buah jeruk instan segar.',
                ],
                [
                    'name' => 'Mama Lemon Jeruk Nipis 680ml',
                    'sku_code' => 'WNG-MML-JRK-680',
                    'barcode' => '8998866400012',
                    'category' => 'Care / Dishwash',
                    'brand' => 'Mama Lemon',
                    'price' => 11000,
                    'uom' => 'Pouch',
                    'description' => 'Cairan pencuci piring ekstrak jeruk nipis ampuh hilangkan lemak.',
                ],
                [
                    'name' => 'Glico Wings Haku Matcha Cone 110ml',
                    'sku_code' => 'WNG-GLC-HKU-110',
                    'barcode' => '8998866500018',
                    'category' => 'Ice Cream',
                    'brand' => 'Glico Wings',
                    'price' => 10000,
                    'uom' => 'Pcs',
                    'description' => 'Es krim cone premium rasa Japanese Green Tea Matcha.',
                ],
                [
                    'name' => 'Glico Wings Waku Waku Choco 60ml',
                    'sku_code' => 'WNG-GLC-WKU-60',
                    'barcode' => '8998866500025',
                    'category' => 'Ice Cream',
                    'brand' => 'Glico Wings',
                    'price' => 4000,
                    'uom' => 'Pcs',
                    'description' => 'Es krim stik renyah berlapis cokelat lezat.',
                ],
            ];

            foreach ($wingsProducts as $p) {
                Product::updateOrCreate(
                    ['sku_code' => $p['sku_code']],
                    array_merge($p, [
                        'principal_id' => $wingsSurya->id,
                        'company_id' => $companyId,
                        'is_active' => true,
                    ])
                );
            }
        }

        if ($lionWings && $lionWings->id !== $wingsSurya?->id) {
            $lionProducts = [
                [
                    'name' => 'Nuvo Family Antibacterial Soap Merah 76g',
                    'sku_code' => 'LNW-NVO-MRH-76',
                    'barcode' => '8998866600015',
                    'category' => 'Personal Care',
                    'brand' => 'Nuvo',
                    'price' => 4500,
                    'uom' => 'Pcs',
                    'description' => 'Sabun batang perlindungan kuman dan antibakteri.',
                ],
                [
                    'name' => 'Nuvo Family Liquid Body Wash Pouch 450ml',
                    'sku_code' => 'LNW-NVO-BW-450',
                    'barcode' => '8998866600022',
                    'category' => 'Personal Care',
                    'brand' => 'Nuvo',
                    'price' => 21500,
                    'uom' => 'Pouch',
                    'description' => 'Sabun mandi cair antibakteri keluarga harum segar.',
                ],
                [
                    'name' => 'Ciptadent Maxi Complete Toothpaste 190g',
                    'sku_code' => 'LNW-CPT-CPT-190',
                    'barcode' => '8998866700012',
                    'category' => 'Oral Care',
                    'brand' => 'Ciptadent',
                    'price' => 12500,
                    'uom' => 'Tube',
                    'description' => 'Pasta gigi perlindungan gigi berlubang dan nafas segar.',
                ],
                [
                    'name' => 'Zinc Active Fresh Anti Dandruff Shampoo 170ml',
                    'sku_code' => 'LNW-ZNC-FSH-170',
                    'barcode' => '8998866800019',
                    'category' => 'Personal Care',
                    'brand' => 'Zinc',
                    'price' => 22000,
                    'uom' => 'Btl',
                    'description' => 'Shampoo anti ketombe dengan Zinc PTO dan cooling menthol.',
                ],
                [
                    'name' => 'Kodomo Baby Wipes Rice Milk 50 Sheets',
                    'sku_code' => 'LNW-KDM-WPS-50',
                    'barcode' => '8998866900016',
                    'category' => 'Baby Care',
                    'brand' => 'Kodomo',
                    'price' => 15500,
                    'uom' => 'Pack',
                    'description' => 'Tisu basah bayi formula lembut bebas alkohol.',
                ],
            ];

            foreach ($lionProducts as $p) {
                Product::updateOrCreate(
                    ['sku_code' => $p['sku_code']],
                    array_merge($p, [
                        'principal_id' => $lionWings->id,
                        'company_id' => $companyId,
                        'is_active' => true,
                    ])
                );
            }
        }

        // 2. DAESANG MAMASUKA
        $mamasuka = Principal::where('code', 'PR-DAESANG-MAMASUKA')
            ->orWhere('name', 'LIKE', '%MAMASUKA%')
            ->orWhere('name', 'LIKE', '%DAESANG%')
            ->orWhere('subdomain', 'mamasuka')
            ->first();

        if ($mamasuka) {
            $mamasukaProducts = [
                [
                    'name' => 'MamaSuka Tepung Bumbu Serbaguna 200g',
                    'sku_code' => 'MSK-TPG-SBG-200',
                    'barcode' => '8801052100015',
                    'category' => 'Seasoning & Flour',
                    'brand' => 'MamaSuka',
                    'price' => 6500,
                    'uom' => 'Bks',
                    'description' => 'Tepung bumbu renyah gurih serbaguna untuk ayam dan tempe.',
                ],
                [
                    'name' => 'MamaSuka Tepung Bakwan Crispy 200g',
                    'sku_code' => 'MSK-TPG-BKW-200',
                    'barcode' => '8801052100022',
                    'category' => 'Seasoning & Flour',
                    'brand' => 'MamaSuka',
                    'price' => 6500,
                    'uom' => 'Bks',
                    'description' => 'Tepung bumbu khusus bakwan goreng garing dan empuk.',
                ],
                [
                    'name' => 'MamaSuka Gim Bap Rumput Laut Panggang 2x4.5g',
                    'sku_code' => 'MSK-RLT-GIM-45',
                    'barcode' => '8801052200012',
                    'category' => 'Snack / Seaweed',
                    'brand' => 'MamaSuka',
                    'price' => 13500,
                    'uom' => 'Pack',
                    'description' => 'Rumput laut panggang rasa original asin gurih khas Korea.',
                ],
                [
                    'name' => 'MamaSuka Bon Nori Rumput Laut Tabur 30g',
                    'sku_code' => 'MSK-BNR-TAB-30',
                    'barcode' => '8801052200029',
                    'category' => 'Snack / Topping',
                    'brand' => 'MamaSuka',
                    'price' => 14500,
                    'uom' => 'Btl',
                    'description' => 'Taburan nori wijen gurih renyah untuk teman nasi hangat.',
                ],
                [
                    'name' => 'Delisaos Saus Tiram Manis 260ml',
                    'sku_code' => 'MSK-DLS-STM-260',
                    'barcode' => '8801052300019',
                    'category' => 'Sauce & Condiment',
                    'brand' => 'Delisaos',
                    'price' => 16000,
                    'uom' => 'Btl',
                    'description' => 'Saus tiram gurih manis kental kualitas premium.',
                ],
                [
                    'name' => 'Delisaos Saus Pasta Bolognese 315g',
                    'sku_code' => 'MSK-DLS-BLG-315',
                    'barcode' => '8801052300026',
                    'category' => 'Sauce & Condiment',
                    'brand' => 'Delisaos',
                    'price' => 19500,
                    'uom' => 'Pouch',
                    'description' => 'Saus pasta tomat daging cincang kaya rempah.',
                ],
                [
                    'name' => 'MamaSuka Bumbu Kuah Bakso Sachet 8g',
                    'sku_code' => 'MSK-BMB-KHB-8G',
                    'barcode' => '8801052400016',
                    'category' => 'Seasoning & Broth',
                    'brand' => 'MamaSuka',
                    'price' => 2500,
                    'uom' => 'Sachet',
                    'description' => 'Bumbu kaldu kuah bakso sedap dan gurih mantap.',
                ],
                [
                    'name' => 'MamaSuka Mayonais Original Tube 300g',
                    'sku_code' => 'MSK-MYN-ORI-300',
                    'barcode' => '8801052500013',
                    'category' => 'Sauce / Dressing',
                    'brand' => 'MamaSuka',
                    'price' => 24000,
                    'uom' => 'Tube',
                    'description' => 'Mayonais lembut gurih asam segar untuk salad dan burger.',
                ],
                [
                    'name' => 'Daesang Corn Oil Minyak Jagung Murni 900ml',
                    'sku_code' => 'MSK-DSG-CRN-900',
                    'barcode' => '8801052600010',
                    'category' => 'Cooking Oil',
                    'brand' => 'Daesang',
                    'price' => 68000,
                    'uom' => 'Btl',
                    'description' => 'Minyak jagung non-kolesterol sehat untuk menumis dan menggoreng.',
                ],
            ];

            foreach ($mamasukaProducts as $p) {
                Product::updateOrCreate(
                    ['sku_code' => $p['sku_code']],
                    array_merge($p, [
                        'principal_id' => $mamasuka->id,
                        'company_id' => $companyId,
                        'is_active' => true,
                    ])
                );
            }
        }

        // 3. FONTERRA BRANDS INDONESIA
        $fonterra = Principal::where('code', 'PR-FONTERRA-BRANDS')
            ->orWhere('name', 'LIKE', '%FONTERRA%')
            ->orWhere('subdomain', 'fonterra')
            ->first();

        if ($fonterra) {
            $fonterraProducts = [
                [
                    'name' => 'Anlene Gold 5X Vanilla Susu Bubuk 650g',
                    'sku_code' => 'FNT-ALN-GLD-650',
                    'barcode' => '9415007000015',
                    'category' => 'Adult Milk',
                    'brand' => 'Anlene',
                    'price' => 108000,
                    'uom' => 'Box',
                    'description' => 'Susu berkalsium tinggi dengan nutrisi 5X untuk tulang, sendi, dan otot.',
                ],
                [
                    'name' => 'Anlene Actifit 3X Cokelat Susu Bubuk 600g',
                    'sku_code' => 'FNT-ALN-ACT-600',
                    'barcode' => '9415007000022',
                    'category' => 'Adult Milk',
                    'brand' => 'Anlene',
                    'price' => 82000,
                    'uom' => 'Box',
                    'description' => 'Susu aktif harian rasa cokelat nikmat dengan kalsium dan kolagen.',
                ],
                [
                    'name' => 'Anchor Salted Butter Block 227g',
                    'sku_code' => 'FNT-ANC-BTR-227',
                    'barcode' => '9415007100012',
                    'category' => 'Butter & Dairy',
                    'brand' => 'Anchor',
                    'price' => 47000,
                    'uom' => 'Block',
                    'description' => 'Mentega murni dari susu sapi New Zealand berkualitas tinggi.',
                ],
                [
                    'name' => 'Anchor Unsalted Butter Foil 227g',
                    'sku_code' => 'FNT-ANC-USB-227',
                    'barcode' => '9415007100029',
                    'category' => 'Butter & Dairy',
                    'brand' => 'Anchor',
                    'price' => 48500,
                    'uom' => 'Block',
                    'description' => 'Mentega tawar murni untuk baking kue dan masakan bayi/MPASI.',
                ],
                [
                    'name' => 'Anchor Cheddar Cheese Block 165g',
                    'sku_code' => 'FNT-ANC-CHD-165',
                    'barcode' => '9415007200019',
                    'category' => 'Cheese',
                    'brand' => 'Anchor',
                    'price' => 26000,
                    'uom' => 'Block',
                    'description' => 'Keju cheddar olahan rasa gurih kaya kalsium mudah diparut.',
                ],
                [
                    'name' => 'Boneeto Cokelat Susu Bubuk Anak 700g',
                    'sku_code' => 'FNT-BNT-CKL-700',
                    'barcode' => '9415007300016',
                    'category' => 'Kids Milk',
                    'brand' => 'Boneeto',
                    'price' => 75000,
                    'uom' => 'Box',
                    'description' => 'Susu pertumbuhan anak tinggi kalsium dan zat besi rasa cokelat.',
                ],
                [
                    'name' => 'Anchor Extra Yield Cooking Cream 1L',
                    'sku_code' => 'FNT-ANC-CRM-1L',
                    'barcode' => '9415007400013',
                    'category' => 'Culinary Cream',
                    'brand' => 'Anchor',
                    'price' => 79000,
                    'uom' => 'Tetra',
                    'description' => 'Krim masak kental tidak mudah pecah saat dipanaskan.',
                ],
            ];

            foreach ($fonterraProducts as $p) {
                Product::updateOrCreate(
                    ['sku_code' => $p['sku_code']],
                    array_merge($p, [
                        'principal_id' => $fonterra->id,
                        'company_id' => $companyId,
                        'is_active' => true,
                    ])
                );
            }
        }

        // 4. DULUX (PT AKZONOBEL)
        $dulux = Principal::where('code', 'PR-DULUX')
            ->orWhere('name', 'LIKE', '%DULUX%')
            ->orWhere('name', 'LIKE', '%AKZONOBEL%')
            ->orWhere('subdomain', 'dulux')
            ->first();

        if ($dulux) {
            $duluxProducts = [
                [
                    'name' => 'Dulux Weathershield Powerflexx Brilliant White 2.5L',
                    'sku_code' => 'DLX-WTS-WHT-25L',
                    'barcode' => '8711115100014',
                    'category' => 'Exterior Paint',
                    'brand' => 'Dulux Weathershield',
                    'price' => 345000,
                    'uom' => 'Can',
                    'description' => 'Cat dinding luar tahan cuaca ekstrem dengan teknologi elastis penutup retak rambut.',
                ],
                [
                    'name' => 'Dulux Catylac Interior Putih 5kg',
                    'sku_code' => 'DLX-CTL-INT-5KG',
                    'barcode' => '8711115200011',
                    'category' => 'Interior Paint',
                    'brand' => 'Catylac',
                    'price' => 135000,
                    'uom' => 'Pail',
                    'description' => 'Cat tembok interior cerah menakjubkan dengan daya sebar luas.',
                ],
                [
                    'name' => 'Dulux EasyClean Anti-Bakteri 2.5L',
                    'sku_code' => 'DLX-ECL-ANT-25L',
                    'barcode' => '8711115300018',
                    'category' => 'Interior Paint',
                    'brand' => 'Dulux EasyClean',
                    'price' => 215000,
                    'uom' => 'Can',
                    'description' => 'Cat interior yang mudah dibersihkan dari noda membandel.',
                ],
                [
                    'name' => 'Dulux Aquashield Pelapis Anti Bocor Abu-Abu 4kg',
                    'sku_code' => 'DLX-AQS-ABU-4KG',
                    'barcode' => '8711115400015',
                    'category' => 'Waterproofing',
                    'brand' => 'Dulux Aquashield',
                    'price' => 195000,
                    'uom' => 'Pail',
                    'description' => 'Pelapis anti bocor 2X lebih tebal dan elastis tahan air.',
                ],
                [
                    'name' => 'Dulux Pentalite Emulsion Soft Almond 2.5L',
                    'sku_code' => 'DLX-PNT-ALM-25L',
                    'barcode' => '8711115500012',
                    'category' => 'Interior Paint',
                    'brand' => 'Dulux Pentalite',
                    'price' => 190000,
                    'uom' => 'Can',
                    'description' => 'Cat interior premium dengan hasil akhir matt halus dan mewah.',
                ],
            ];

            foreach ($duluxProducts as $p) {
                Product::updateOrCreate(
                    ['sku_code' => $p['sku_code']],
                    array_merge($p, [
                        'principal_id' => $dulux->id,
                        'company_id' => $companyId,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
