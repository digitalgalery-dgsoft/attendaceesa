<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // PostgreSQL: Find all check constraints on the visit_reports table
            $constraints = DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'visit_reports'::regclass AND contype = 'c'");
            
            // Drop all check constraints (including status enum checks and unsigned integer checks)
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE visit_reports DROP CONSTRAINT IF EXISTS \"{$constraint->conname}\"");
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
