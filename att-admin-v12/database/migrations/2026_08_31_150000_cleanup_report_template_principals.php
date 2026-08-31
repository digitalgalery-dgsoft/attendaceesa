<?php

use App\Models\Principal;
use App\Models\ReportTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bersihkan Dulux Templates (RPT-DULUX-*)
        $duluxPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%ICI%')
              ->orWhere('name', 'LIKE', '%PAINT%')
              ->orWhere('name', 'LIKE', '%DULUX%')
              ->orWhere('name', 'LIKE', '%AKZONOBEL%')
              ->orWhere('code', 'LIKE', '%ICI%')
              ->orWhere('code', 'LIKE', '%DULUX%');
        })->get();

        $duluxPrincipalIds = $duluxPrincipals->pluck('id')->toArray();
        $primaryDuluxId = $duluxPrincipals->first()?->id;

        $duluxTemplates = ReportTemplate::where('code', 'LIKE', 'RPT-DULUX-%')
            ->orWhere('title', 'LIKE', '%Dulux%')
            ->get();

        foreach ($duluxTemplates as $template) {
            // Hapus semua relasi principal yang BUKAN prinsiple Dulux
            DB::table('report_template_principal')
                ->where('report_template_id', $template->id)
                ->whereNotIn('principal_id', $duluxPrincipalIds)
                ->delete();

            // Pastikan entitas Dulux terhubung
            if (!empty($duluxPrincipalIds)) {
                $template->principals()->sync($duluxPrincipalIds);
            }

            if ($primaryDuluxId) {
                $template->update(['principal_id' => $primaryDuluxId]);
            }
        }

        // 2. Bersihkan Fonterra Templates (RPT-FONTERRA-*)
        $fonterraPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%FONTERRA%')
              ->orWhere('code', 'LIKE', '%FONTERRA%')
              ->orWhere('subdomain', 'LIKE', '%FONTERRA%');
        })->pluck('id')->toArray();

        if (!empty($fonterraPrincipals)) {
            $fonterraTemplates = ReportTemplate::where('code', 'LIKE', 'RPT-FONTERRA-%')
                ->orWhere('title', 'LIKE', '%Fonterra%')
                ->get();

            foreach ($fonterraTemplates as $template) {
                DB::table('report_template_principal')
                    ->where('report_template_id', $template->id)
                    ->whereNotIn('principal_id', $fonterraPrincipals)
                    ->delete();

                $template->principals()->sync($fonterraPrincipals);
                $template->update(['principal_id' => $fonterraPrincipals[0]]);
            }
        }

        // 3. Bersihkan Wings Templates (RPT-WINGS-*)
        $wingsPrincipals = Principal::where(function ($q) {
            $q->where('name', 'LIKE', '%WINGS%')
              ->orWhere('code', 'LIKE', '%WINGS%')
              ->orWhere('subdomain', 'LIKE', '%WINGS%');
        })->pluck('id')->toArray();

        if (!empty($wingsPrincipals)) {
            $wingsTemplates = ReportTemplate::where('code', 'LIKE', 'RPT-WINGS-%')
                ->orWhere('title', 'LIKE', '%Wings%')
                ->get();

            foreach ($wingsTemplates as $template) {
                DB::table('report_template_principal')
                    ->where('report_template_id', $template->id)
                    ->whereNotIn('principal_id', $wingsPrincipals)
                    ->delete();

                $template->principals()->sync($wingsPrincipals);
                $template->update(['principal_id' => $wingsPrincipals[0]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
