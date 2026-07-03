<?php
$dir = __DIR__ . '/database/migrations';
$files = scandir($dir);
foreach ($files as $file) {
    if (strpos($file, 'create_companies_table') !== false) {
        file_put_contents("$dir/$file", getCompaniesTable());
    } elseif (strpos($file, 'create_branches_table') !== false) {
        file_put_contents("$dir/$file", getBranchesTable());
    } elseif (strpos($file, 'create_departments_table') !== false) {
        file_put_contents("$dir/$file", getDepartmentsTable());
    } elseif (strpos($file, 'create_positions_table') !== false) {
        file_put_contents("$dir/$file", getPositionsTable());
    } elseif (strpos($file, 'create_employees_table') !== false) {
        file_put_contents("$dir/$file", getEmployeesTable());
    } elseif (strpos($file, 'create_work_locations_table') !== false) {
        file_put_contents("$dir/$file", getWorkLocationsTable());
    } elseif (strpos($file, 'create_shifts_table') !== false) {
        file_put_contents("$dir/$file", getShiftsTable());
    } elseif (strpos($file, 'create_employee_schedules_table') !== false) {
        file_put_contents("$dir/$file", getEmployeeSchedulesTable());
    } elseif (strpos($file, 'create_holidays_table') !== false) {
        file_put_contents("$dir/$file", getHolidaysTable());
    } elseif (strpos($file, 'create_attendances_table') !== false) {
        file_put_contents("$dir/$file", getAttendancesTable());
    } elseif (strpos($file, 'create_attendance_logs_table') !== false) {
        file_put_contents("$dir/$file", getAttendanceLogsTable());
    }
}

function getCompaniesTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('companies', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name', 150);
            \$table->string('code', 50)->unique();
            \$table->string('timezone', 80)->default('Asia/Jakarta');
            \$table->string('logo', 255)->nullable();
            \$table->text('address')->nullable();
            \$table->string('phone', 50)->nullable();
            \$table->string('email', 150)->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->json('settings')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
        });
    }
    public function down() { Schema::dropIfExists('companies'); }
};
EOF;
}

function getBranchesTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('branches', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->string('name', 150);
            \$table->string('code', 50);
            \$table->text('address')->nullable();
            \$table->decimal('latitude', 10, 7)->nullable();
            \$table->decimal('longitude', 10, 7)->nullable();
            \$table->integer('radius_meter')->default(100);
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
            \$table->index(['company_id', 'code']);
        });
    }
    public function down() { Schema::dropIfExists('branches'); }
};
EOF;
}

function getDepartmentsTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('departments', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->string('name', 150);
            \$table->string('code', 50)->nullable();
            \$table->foreignId('parent_id')->nullable()->constrained('departments')->onDelete('set null');
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
        });
    }
    public function down() { Schema::dropIfExists('departments'); }
};
EOF;
}

function getPositionsTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('positions', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->string('name', 150);
            \$table->string('code', 50)->nullable();
            \$table->integer('level')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
        });
    }
    public function down() { Schema::dropIfExists('positions'); }
};
EOF;
}

function getEmployeesTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('employees', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            \$table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            \$table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null');
            \$table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null');
            \$table->string('employee_no', 80);
            \$table->string('full_name', 150);
            \$table->enum('gender', ['male', 'female'])->nullable();
            \$table->date('birth_date')->nullable();
            \$table->date('join_date')->nullable();
            \$table->date('resign_date')->nullable();
            \$table->enum('employment_status', ['permanent', 'contract', 'probation', 'intern', 'resigned']);
            \$table->string('phone', 50)->nullable();
            \$table->string('email', 150)->nullable();
            \$table->text('address')->nullable();
            \$table->string('photo', 255)->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
            
            \$table->unique(['company_id', 'employee_no']);
            \$table->index(['company_id', 'branch_id', 'department_id', 'supervisor_id']);
        });
    }
    public function down() { Schema::dropIfExists('employees'); }
};
EOF;
}

function getWorkLocationsTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('work_locations', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            \$table->string('name', 150);
            \$table->enum('type', ['office', 'client', 'project', 'warehouse', 'other']);
            \$table->text('address')->nullable();
            \$table->decimal('latitude', 10, 7);
            \$table->decimal('longitude', 10, 7);
            \$table->integer('radius_meter')->default(100);
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
        });
    }
    public function down() { Schema::dropIfExists('work_locations'); }
};
EOF;
}

function getShiftsTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('shifts', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->string('name', 100);
            \$table->string('code', 50)->nullable();
            \$table->time('start_time');
            \$table->time('end_time');
            \$table->time('break_start_time')->nullable();
            \$table->time('break_end_time')->nullable();
            \$table->integer('grace_checkin_minutes')->default(0);
            \$table->integer('grace_checkout_minutes')->default(0);
            \$table->boolean('is_cross_day')->default(false);
            \$table->boolean('required_checkin')->default(true);
            \$table->boolean('required_checkout')->default(true);
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
        });
    }
    public function down() { Schema::dropIfExists('shifts'); }
};
EOF;
}

function getEmployeeSchedulesTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('employee_schedules', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            \$table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            \$table->foreignId('work_location_id')->nullable()->constrained('work_locations')->onDelete('set null');
            \$table->date('schedule_date');
            \$table->enum('schedule_type', ['workday', 'dayoff', 'holiday', 'remote', 'field']);
            \$table->dateTime('planned_start_at')->nullable();
            \$table->dateTime('planned_end_at')->nullable();
            \$table->text('note')->nullable();
            \$table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            \$table->timestamps();
            
            \$table->unique(['employee_id', 'schedule_date']);
            \$table->index(['schedule_date', 'shift_id', 'work_location_id']);
        });
    }
    public function down() { Schema::dropIfExists('employee_schedules'); }
};
EOF;
}

function getHolidaysTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('holidays', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            \$table->date('holiday_date');
            \$table->string('name', 150);
            \$table->enum('type', ['national', 'company', 'regional']);
            \$table->boolean('is_paid')->default(true);
            \$table->timestamps();
            
            \$table->unique(['company_id', 'holiday_date']);
        });
    }
    public function down() { Schema::dropIfExists('holidays'); }
};
EOF;
}

function getAttendancesTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('attendances', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            \$table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->onDelete('set null');
            \$table->date('attendance_date');
            \$table->enum('status', ['present', 'late', 'absent', 'permit', 'sick', 'leave', 'holiday', 'dayoff', 'incomplete']);
            \$table->dateTime('checkin_at')->nullable();
            \$table->dateTime('checkout_at')->nullable();
            \$table->unsignedBigInteger('checkin_log_id')->nullable();
            \$table->unsignedBigInteger('checkout_log_id')->nullable();
            \$table->integer('work_duration_minutes')->default(0);
            \$table->integer('late_minutes')->default(0);
            \$table->integer('early_leave_minutes')->default(0);
            \$table->integer('overtime_minutes')->default(0);
            \$table->boolean('is_manual_correction')->default(false);
            \$table->text('correction_note')->nullable();
            \$table->timestamps();
            
            \$table->unique(['employee_id', 'attendance_date']);
            \$table->index(['attendance_date', 'status']);
        });
    }
    public function down() { Schema::dropIfExists('attendances'); }
};
EOF;
}

function getAttendanceLogsTable() {
    return <<<EOF
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('attendance_logs', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('attendance_id')->nullable()->constrained('attendances')->onDelete('cascade');
            \$table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            \$table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->onDelete('set null');
            \$table->unsignedBigInteger('itinerary_item_id')->nullable();
            \$table->enum('log_type', ['checkin', 'checkout', 'visit_in', 'visit_out']);
            \$table->dateTime('logged_at');
            \$table->dateTime('client_logged_at')->nullable();
            \$table->decimal('latitude', 10, 7)->nullable();
            \$table->decimal('longitude', 10, 7)->nullable();
            \$table->decimal('accuracy_meter', 8, 2)->nullable();
            \$table->decimal('altitude', 10, 2)->nullable();
            \$table->text('address_text')->nullable();
            \$table->string('photo_path', 255)->nullable();
            \$table->text('note')->nullable();
            \$table->enum('source', ['android', 'web_admin', 'system', 'import']);
            \$table->enum('validation_status', ['valid', 'warning', 'invalid', 'pending']);
            \$table->text('validation_message')->nullable();
            \$table->boolean('is_inside_geofence')->nullable();
            \$table->decimal('distance_from_location_meter', 10, 2)->nullable();
            \$table->unsignedBigInteger('device_id')->nullable();
            \$table->string('ip_address', 80)->nullable();
            \$table->text('user_agent')->nullable();
            \$table->json('metadata')->nullable();
            \$table->timestamps();
            
            \$table->index(['employee_id', 'logged_at']);
            \$table->index(['log_type', 'attendance_id', 'itinerary_item_id']);
        });
    }
    public function down() { Schema::dropIfExists('attendance_logs'); }
};
EOF;
}
