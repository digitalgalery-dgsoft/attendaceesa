<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan seluruh entitas resmi PT WINGS SURYA (khusus PT Wings Surya)
        $wingsSuryaPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS SURYA%')
              ->orWhere(function ($sub) {
                  $sub->where('name', 'LIKE', '%WINGS%')
                      ->where('name', 'NOT LIKE', '%LION%')
                      ->where('name', 'NOT LIKE', '%GROUP%');
              });
        })->get();

        if ($wingsSuryaPrincipals->isEmpty()) {
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
            $wingsSuryaPrincipals = collect([$primaryWings]);
        }

        $wingsSuryaIds = $wingsSuryaPrincipals->pluck('id')->toArray();
        $primaryWings = $wingsSuryaPrincipals->first();

        // 2. Update master produk Mie Sedaap agar principal_id mengarah ke PT WINGS SURYA
        Product::where(function ($q) {
            $q->where('brand', 'LIKE', '%SEDAAP%')
              ->orWhere('name', 'LIKE', '%MIE SEDAP%')
              ->orWhere('name', 'LIKE', '%MIE SEDAAP%');
        })->update(['principal_id' => $primaryWings->id]);

        $mieSedaapSkus = Product::where(function ($q) {
            $q->where('brand', 'LIKE', '%SEDAAP%')
              ->orWhere('name', 'LIKE', '%MIE SEDAP%')
              ->orWhere('name', 'LIKE', '%MIE SEDAAP%');
        })->pluck('id')->toArray();

        // 3. Pastikan template MBR Sales dan MBR Free Taste hanya terhubung ke PT WINGS SURYA saja
        $mbrTemplates = ReportTemplate::whereIn('code', [
            'RPT-WINGS-MBR-SALES-01',
            'RPT-WINGS-MBR-FREETASTE-01',
        ])->get();

        foreach ($mbrTemplates as $tpl) {
            $tpl->principal_id = $primaryWings->id;
            $tpl->save();

            // Sync hanya ke entitas PT WINGS SURYA (detach PT LION WINGS & WINGS GROUP INDONESIA)
            $tpl->principals()->sync($wingsSuryaIds);

            // Pastikan seluruh 40 produk Mie Sedaap ter-sync ke template
            if (!empty($mieSedaapSkus)) {
                $tpl->products()->syncWithoutDetaching($mieSedaapSkus);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
