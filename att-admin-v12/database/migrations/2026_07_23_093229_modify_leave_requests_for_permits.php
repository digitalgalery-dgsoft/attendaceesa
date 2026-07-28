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
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('type')->change();
            $table->string('sub_type')->nullable()->after('type');
            $table->enum('head_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('head_approval_status');
            $table->timestamp('head_approved_at')->nullable()->after('head_approved_by');
            
            $table->enum('hrd_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('head_approved_at');
            $table->foreignId('hrd_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('hrd_approval_status');
            $table->timestamp('hrd_approved_at')->nullable()->after('hrd_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'sub_type',
                'head_approval_status',
                'head_approved_by',
                'head_approved_at',
                'hrd_approval_status',
                'hrd_approved_by',
                'hrd_approved_at'
            ]);
            // Reverting `type` back to enum is tricky in down(), skipping for safety
        });
    }
};
