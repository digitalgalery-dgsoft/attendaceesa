<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->onDelete('set null');
            $table->unsignedBigInteger('itinerary_item_id')->nullable();
            $table->enum('log_type', ['checkin', 'checkout', 'visit_in', 'visit_out']);
            $table->dateTime('logged_at');
            $table->dateTime('client_logged_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meter', 8, 2)->nullable();
            $table->decimal('altitude', 10, 2)->nullable();
            $table->text('address_text')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->text('note')->nullable();
            $table->enum('source', ['android', 'web_admin', 'system', 'import']);
            $table->enum('validation_status', ['valid', 'warning', 'invalid', 'pending']);
            $table->text('validation_message')->nullable();
            $table->boolean('is_inside_geofence')->nullable();
            $table->decimal('distance_from_location_meter', 10, 2)->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('ip_address', 80)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'logged_at']);
            $table->index(['log_type', 'attendance_id', 'itinerary_item_id']);
        });
    }
    public function down() { Schema::dropIfExists('attendance_logs'); }
};