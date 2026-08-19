<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('odoo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 64)->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('sync_type', 30)->default('all'); // employee, principal, all
            $table->string('trigger_type', 20)->default('cron'); // cron, manual
            $table->string('status', 20)->default('success'); // success, failed, partial
            $table->integer('new_count')->default(0);
            $table->integer('update_count')->default(0);
            $table->integer('resign_count')->default(0);
            $table->integer('total_employee_count')->default(0);
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_sync_logs');
    }
};
