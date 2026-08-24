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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_id')->constrained('principals')->cascadeOnDelete();
            $table->string('title');
            $table->string('code')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('category')->default('general')->index(); // offtake, stock, pricing, display, competitor, survey, posm, expired_date, general
            $table->boolean('require_gps')->default(true);
            $table->boolean('require_signature')->default(false);
            $table->integer('min_photos')->default(0);
            $table->integer('max_photos')->default(5);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
