<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('odoo_url')->nullable()->after('gemini_model');
            $table->string('odoo_db')->nullable()->after('odoo_url');
            $table->string('odoo_username')->nullable()->after('odoo_db');
            $table->string('odoo_api_key')->nullable()->after('odoo_username');
            $table->boolean('odoo_sync_enabled')->default(false)->after('odoo_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['odoo_url', 'odoo_db', 'odoo_username', 'odoo_api_key', 'odoo_sync_enabled']);
        });
    }
};
