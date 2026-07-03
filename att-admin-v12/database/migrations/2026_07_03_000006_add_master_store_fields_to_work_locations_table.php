<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_locations', function (Blueprint $table) {
            $table->string('region')->nullable()->after('type');
            $table->string('area')->nullable()->after('region');
            $table->string('sub_area')->nullable()->after('area');
            $table->string('channel')->nullable()->after('sub_area');
            $table->string('account')->nullable()->after('channel');
            $table->string('timezone')->nullable()->after('account');
            $table->enum('status', ['pending', 'active', 'inactive'])->default('active')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('work_locations', function (Blueprint $table) {
            $table->dropColumn(['region', 'area', 'sub_area', 'channel', 'account', 'timezone', 'status']);
        });
    }
};
