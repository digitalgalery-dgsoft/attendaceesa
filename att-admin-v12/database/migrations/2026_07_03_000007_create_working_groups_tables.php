<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('area')->nullable();
            $table->string('sub_area')->nullable();
            $table->date('data_applied_date')->nullable();
            $table->timestamps();
        });

        Schema::create('working_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('working_group_id')->constrained('working_groups')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('master_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->integer('late_tolerance')->default(15);
            $table->foreignId('first_visit_store_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('working_group_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('working_group_id')->constrained('working_groups')->cascadeOnDelete();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->integer('late_tolerance')->default(15);
            $table->boolean('routing_active')->default(false);
            $table->foreignId('store_assignment_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['working_group_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_group_rules');
        Schema::dropIfExists('working_group_members');
        Schema::dropIfExists('working_groups');
    }
};
