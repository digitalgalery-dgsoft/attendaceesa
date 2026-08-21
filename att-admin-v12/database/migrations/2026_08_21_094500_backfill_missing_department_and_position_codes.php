<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations to populate unique codes for all departments and positions with empty or null code.
     */
    public function up(): void
    {
        // 1. Backfill departments with empty or null code
        $departments = DB::table('departments')
            ->whereNull('code')
            ->orWhere('code', '')
            ->get();

        foreach ($departments as $dept) {
            DB::table('departments')
                ->where('id', $dept->id)
                ->update([
                    'code' => 'DEP-' . strtoupper(Str::random(5)),
                ]);
        }

        // 2. Backfill positions with empty or null code
        $positions = DB::table('positions')
            ->whereNull('code')
            ->orWhere('code', '')
            ->get();

        foreach ($positions as $pos) {
            DB::table('positions')
                ->where('id', $pos->id)
                ->update([
                    'code' => 'POS-' . strtoupper(Str::random(5)),
                ]);
        }

        // 3. Backfill branches with empty or null code
        $branches = DB::table('branches')
            ->whereNull('code')
            ->orWhere('code', '')
            ->get();

        foreach ($branches as $br) {
            DB::table('branches')
                ->where('id', $br->id)
                ->update([
                    'code' => 'BRN-' . strtoupper(Str::random(5)),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed for backfilling codes
    }
};
