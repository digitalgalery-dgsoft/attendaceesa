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
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->string('target_type')->nullable()->after('action_taken');
            $table->string('target_qty')->nullable()->after('target_type');
            $table->string('actual_qty')->nullable()->after('target_qty');
            $table->string('target_value')->nullable()->after('actual_qty');
            $table->string('actual_value')->nullable()->after('target_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn([
                'target_type',
                'target_qty',
                'actual_qty',
                'target_value',
                'actual_value',
            ]);
        });
    }
};
