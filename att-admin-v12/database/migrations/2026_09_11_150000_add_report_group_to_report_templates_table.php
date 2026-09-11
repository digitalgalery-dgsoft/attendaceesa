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
        if (!Schema::hasColumn('report_templates', 'report_group')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->string('report_group', 50)->default('regular')->nullable()->index()->after('category');
            });

            // Set seluruh template yang sudah ada sebagai 'regular'
            DB::table('report_templates')->whereNull('report_group')->update(['report_group' => 'regular']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('report_templates', 'report_group')) {
            Schema::table('report_templates', function (Blueprint $table) {
                $table->dropColumn('report_group');
            });
        }
    }
};
