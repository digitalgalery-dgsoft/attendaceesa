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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('odoo_url')->nullable();
            $table->string('odoo_db')->nullable();
            $table->string('odoo_username')->nullable();
            $table->string('odoo_api_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['odoo_url', 'odoo_db', 'odoo_username', 'odoo_api_key']);
        });
    }
};
