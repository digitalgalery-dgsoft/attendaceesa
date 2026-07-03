<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_item_id')->constrained('itinerary_items')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('issue')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('target')->nullable();
            $table->text('actual')->nullable();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open_issue', 'action_taken', 'completed', 'overdue'])->default('open_issue');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};
