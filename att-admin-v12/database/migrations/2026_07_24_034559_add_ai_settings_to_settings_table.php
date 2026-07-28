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
            $table->longText('gemini_api_keys')->nullable();
            $table->string('gemini_model')->default('gemini-1.5-flash')->nullable();
            $table->integer('current_gemini_key_index')->default(0);
            
            $table->string('sumopod_api_key')->nullable();
            $table->string('sumopod_model')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_api_keys',
                'gemini_model',
                'current_gemini_key_index',
                'sumopod_api_key',
                'sumopod_model',
            ]);
        });
    }
};
