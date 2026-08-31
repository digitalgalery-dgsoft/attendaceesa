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
            $table->string('grooming_condition')->nullable()->after('position');
            $table->text('active_promo')->nullable()->after('grooming_condition');
            $table->text('oos_products')->nullable()->after('active_promo');
            $table->text('other_issues')->nullable()->after('oos_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn([
                'grooming_condition',
                'active_promo',
                'oos_products',
                'other_issues',
            ]);
        });
    }
};
