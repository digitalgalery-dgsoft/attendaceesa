<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->onDelete('set null');
            $table->date('attendance_date');
            $table->enum('status', ['present', 'late', 'absent', 'permit', 'sick', 'leave', 'holiday', 'dayoff', 'incomplete']);
            $table->dateTime('checkin_at')->nullable();
            $table->dateTime('checkout_at')->nullable();
            $table->unsignedBigInteger('checkin_log_id')->nullable();
            $table->unsignedBigInteger('checkout_log_id')->nullable();
            $table->integer('work_duration_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->boolean('is_manual_correction')->default(false);
            $table->text('correction_note')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
        });
    }
    public function down() { Schema::dropIfExists('attendances'); }
};