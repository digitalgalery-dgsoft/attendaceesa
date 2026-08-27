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
        $template = ReportTemplate::where('code', 'RPT-WINGS-PENJUALAN-HADIAH-01')->first();
        if ($template) {
            ReportFormField::where('report_template_id', $template->id)
                ->where('field_name', 'foto_struk_hadiah')
                ->update([
                    'field_type' => 'multi_photo',
                    'field_label' => 'Foto Bukti Struk Kasir / Penyerahan Hadiah (Multi-Foto)',
                    'help_text' => 'Bisa ambil lebih dari 1 foto bukti struk pembelian dan penyerahan hadiah ke konsumen.',
                ]);
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
