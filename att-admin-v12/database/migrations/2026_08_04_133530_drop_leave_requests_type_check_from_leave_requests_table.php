<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE leave_requests DROP CONSTRAINT IF EXISTS leave_requests_type_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible without knowing exactly what values were allowed,
        // but typically:
        // DB::statement("ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_type_check CHECK (type::text = ANY (ARRAY['annual_leave'::character varying, 'medical_leave'::character varying, 'permission'::character varying, 'shift_swap'::character varying, 'extra_off'::character varying, 'store_closed'::character varying]::text[]))");
    }
};
