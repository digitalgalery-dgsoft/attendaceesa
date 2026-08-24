<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel pivot report_template_principal
        if (!Schema::hasTable('report_template_principal')) {
            Schema::create('report_template_principal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
                $table->foreignId('principal_id')->constrained('principals')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['report_template_id', 'principal_id']);
            });
        }

        // 2. Ubah kolom principal_id pada report_templates menjadi nullable jika belum
        Schema::table('report_templates', function (Blueprint $table) {
            $table->foreignId('principal_id')->nullable()->change();
        });

        // 3. Backfill data dari report_templates ke pivot table
        if (Schema::hasTable('report_templates')) {
            $templates = DB::table('report_templates')->whereNotNull('principal_id')->get();
            foreach ($templates as $tmpl) {
                DB::table('report_template_principal')->updateOrInsert(
                    ['report_template_id' => $tmpl->id, 'principal_id' => $tmpl->principal_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_template_principal');
    }
};
