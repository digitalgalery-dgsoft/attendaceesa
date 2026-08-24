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
        Schema::create('report_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_submission_id')->constrained('report_submissions')->cascadeOnDelete();
            $table->foreignId('report_form_field_id')->nullable()->constrained('report_form_fields')->nullOnDelete();
            $table->string('field_name')->index();
            $table->string('field_type');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 15, 2)->nullable();
            $table->json('value_json')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamps();

            $table->index(['report_submission_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_submission_values');
    }
};
