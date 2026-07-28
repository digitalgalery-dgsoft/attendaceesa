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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_company')->nullable();
            $table->decimal('revenue', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('report_date');
            $table->string('status')->default('pending'); // pending, closed, lost
            $table->string('location')->nullable();
            $table->string('receipt_image')->nullable();
            $table->text('ai_insights')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
