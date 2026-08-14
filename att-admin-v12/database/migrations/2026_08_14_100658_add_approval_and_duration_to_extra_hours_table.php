<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extra_hours', function (Blueprint $table) {
            $table->time('end_time')->nullable()->change();
            $table->integer('duration')->nullable()->after('end_time')->comment('Duration in minutes');
            
            $table->enum('head_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('head_approval_status');
            $table->timestamp('head_approved_at')->nullable()->after('head_approved_by');
            
            $table->enum('hrd_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('head_approved_at');
            $table->foreignId('hrd_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('hrd_approval_status');
            $table->timestamp('hrd_approved_at')->nullable()->after('hrd_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('extra_hours', function (Blueprint $table) {
            $table->dropColumn([
                'duration',
                'head_approval_status',
                'head_approved_by',
                'head_approved_at',
                'hrd_approval_status',
                'hrd_approved_by',
                'hrd_approved_at'
            ]);
            // Dropping change from nullable is problematic in SQLite but ok in MySQL/Postgres. We'll skip for safety.
        });
    }
};
