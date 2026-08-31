<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Principal;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Deactivate all principals that have 0 active employees
        Principal::whereDoesntHave('employees', function ($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        })->update(['is_active' => false]);

        // 2. Ensure principals that have active employees are active
        Principal::whereHas('employees', function ($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        })->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed
    }
};
