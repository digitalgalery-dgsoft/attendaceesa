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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('require_checkin_photo')->default(true);
            $table->boolean('require_checkout_photo')->default(true);
            $table->boolean('require_visit_photo')->default(true);
            $table->boolean('use_roster_principle')->default(false);
            $table->boolean('lock_roster')->default(true);
            $table->integer('global_distance_lock')->default(50)->comment('Radius in meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'require_checkin_photo',
                'require_checkout_photo',
                'require_visit_photo',
                'use_roster_principle',
                'lock_roster',
                'global_distance_lock',
            ]);
        });
    }
};
