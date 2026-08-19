<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('title', 200);
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('meeting_type', ['online', 'offline'])->default('offline');
            $table->text('meeting_link')->nullable(); // Zoom, GMeet, Teams link
            $table->string('location_name', 200)->nullable();
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->onDelete('set null');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('radius_meter')->default(100);
            $table->text('notes')->nullable(); // Agenda / Deskripsi
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['meeting_date', 'status']);
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('status', ['invited', 'attended', 'absent'])->default('invited');
            $table->timestamps();

            $table->unique(['meeting_id', 'employee_id']);
        });

        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->dateTime('meet_in_at');
            $table->decimal('meet_in_lat', 10, 7)->nullable();
            $table->decimal('meet_in_lng', 10, 7)->nullable();
            $table->string('meet_in_photo', 255)->nullable();
            $table->dateTime('meet_out_at')->nullable();
            $table->decimal('meet_out_lat', 10, 7)->nullable();
            $table->decimal('meet_out_lng', 10, 7)->nullable();
            $table->string('meet_out_photo', 255)->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->text('report_notes')->nullable();
            $table->enum('status', ['in_meeting', 'completed'])->default('in_meeting');
            $table->timestamps();

            $table->index(['meeting_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
    }
};
