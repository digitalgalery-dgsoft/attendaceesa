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
            if (!Schema::hasColumn('principals', 'subdomain')) {
                $table->string('subdomain')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('principals', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->after('subdomain');
            }
            if (!Schema::hasColumn('principals', 'theme_color')) {
                $table->string('theme_color')->default('#0F52BA')->after('custom_domain');
            }
            if (!Schema::hasColumn('principals', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('theme_color');
            }
            if (!Schema::hasColumn('principals', 'banner_path')) {
                $table->string('banner_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('principals', 'portal_title')) {
                $table->string('portal_title')->nullable()->after('banner_path');
            }
            if (!Schema::hasColumn('principals', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('portal_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('principals', function (Blueprint $table) {
            $table->dropColumn([
                'subdomain',
                'custom_domain',
                'theme_color',
                'logo_path',
                'banner_path',
                'portal_title',
                'is_active',
            ]);
        });
    }
};
