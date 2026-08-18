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
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('principal_id')->nullable()->after('company_id')->constrained('principals')->nullOnDelete();
            
            // We make company_id nullable and remove its constraint to avoid breaking existing data immediately
            $table->dropForeign(['company_id']);
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['principal_id']);
            $table->dropColumn('principal_id');
            
            // Note: Restoring the exact previous state of company_id may fail if there are nulls.
        });
    }
};
