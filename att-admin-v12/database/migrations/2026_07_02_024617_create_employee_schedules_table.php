<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->onDelete('set null');
            $table->date('schedule_date');
            $table->enum('schedule_type', ['workday', 'dayoff', 'holiday', 'remote', 'field']);
            $table->dateTime('planned_start_at')->nullable();
            $table->dateTime('planned_end_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['employee_id', 'schedule_date']);
            $table->index(['schedule_date', 'shift_id', 'work_location_id']);
        });
    }
    public function down() { Schema::dropIfExists('employee_schedules'); }
};