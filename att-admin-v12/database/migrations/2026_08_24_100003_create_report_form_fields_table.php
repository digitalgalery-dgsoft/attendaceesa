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
        Schema::create('report_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->string('field_name'); // unique slug per template
            $table->string('field_label');
            $table->string('field_type'); // text, textarea, number, currency, dropdown, radio, checkbox, date, time, camera_photo, multi_photo, signature, gps_location, barcode_scanner, rating_star, slider
            $table->json('options')->nullable(); // list of options for dropdown/radio/checkbox/sku list
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable(); // min, max, regex, conditional logic
            $table->integer('order_index')->default(0)->index();
            $table->timestamps();

            $table->unique(['report_template_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_form_fields');
    }
};
