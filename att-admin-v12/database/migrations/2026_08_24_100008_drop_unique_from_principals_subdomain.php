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
        Schema::table('principals', function (Blueprint $table) {
            // Drop unique constraint on subdomain agar multiple entitas (misal: ICI Paints Alva, ICI Paints TSM)
            // bisa mengelompok dalam 1 subdomain brand yang sama (misal: dulux.appsend.my.id)
            $table->dropUnique(['subdomain']);
            $table->index('subdomain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('principals', function (Blueprint $table) {
            $table->dropIndex(['subdomain']);
            $table->unique('subdomain');
        });
    }
};
