<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_reports', function (Blueprint $table) {
            // Hapus kolom B2B yang lama
            $table->dropColumn(['client_name', 'client_company', 'revenue', 'pipeline_stage']);
            
            // Tambahkan kolom baru untuk Store/Retail (SPG/MD)
            $table->string('store_name')->after('report_date')->nullable()->comment('Nama Toko/Outlet');
            $table->string('oos_status')->after('store_name')->nullable()->comment('Aman / Kosong');
            $table->text('oos_notes')->after('oos_status')->nullable();
            $table->string('plano_status')->after('oos_notes')->nullable()->comment('Sesuai / Tidak Sesuai');
            $table->text('plano_notes')->after('plano_status')->nullable();
            $table->string('promo_status')->after('plano_notes')->nullable()->comment('Berjalan / Tidak Berjalan');
            $table->text('promo_notes')->after('promo_status')->nullable();
            
            // Foto-foto bukti
            $table->string('photo_oos')->after('promo_notes')->nullable();
            $table->string('photo_plano')->after('photo_oos')->nullable();
            $table->string('photo_promo')->after('photo_plano')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_reports', function (Blueprint $table) {
            $table->dropColumn([
                'store_name',
                'oos_status', 'oos_notes',
                'plano_status', 'plano_notes',
                'promo_status', 'promo_notes',
                'photo_oos', 'photo_plano', 'photo_promo'
            ]);
            
            $table->string('client_name')->nullable();
            $table->string('client_company')->nullable();
            $table->decimal('revenue', 15, 2)->nullable();
            $table->string('pipeline_stage')->nullable();
        });
    }
};
