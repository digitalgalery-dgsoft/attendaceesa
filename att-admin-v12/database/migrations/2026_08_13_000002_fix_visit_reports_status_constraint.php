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
        // Postgres check constraint might not have 'completed' if it was added to the migration file late.
        // We drop the existing check constraint and recreate it to be sure.
        try {
            DB::statement('ALTER TABLE visit_reports DROP CONSTRAINT IF EXISTS visit_reports_status_check');
        } catch (\Exception $e) {
            // Ignore if constraint does not exist
        }

        try {
            DB::statement("ALTER TABLE visit_reports ADD CONSTRAINT visit_reports_status_check CHECK (status::text = ANY (ARRAY['open_issue'::character varying, 'action_taken'::character varying, 'completed'::character varying, 'overdue'::character varying]::text[]))");
        } catch (\Exception $e) {
            // Ignore on error
        }
        
        // Also drop any itinerary_item_id check constraint if it was accidentally created
        try {
            DB::statement('ALTER TABLE visit_reports DROP CONSTRAINT IF EXISTS visit_reports_itinerary_item_id_check');
        } catch (\Exception $e) {
            // Ignore
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
