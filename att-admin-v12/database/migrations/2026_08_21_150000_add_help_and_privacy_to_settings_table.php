<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'help_phone')) {
                $table->string('help_phone')->nullable()->after('mobile_app_version');
            }
            if (!Schema::hasColumn('settings', 'help_whatsapp')) {
                $table->string('help_whatsapp')->nullable()->after('help_phone');
            }
            if (!Schema::hasColumn('settings', 'help_email')) {
                $table->string('help_email')->nullable()->after('help_whatsapp');
            }
            if (!Schema::hasColumn('settings', 'help_hours')) {
                $table->string('help_hours')->nullable()->after('help_email');
            }
            if (!Schema::hasColumn('settings', 'privacy_policy_url')) {
                $table->text('privacy_policy_url')->nullable()->after('help_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['help_phone', 'help_whatsapp', 'help_email', 'help_hours', 'privacy_policy_url']);
        });
    }
};
