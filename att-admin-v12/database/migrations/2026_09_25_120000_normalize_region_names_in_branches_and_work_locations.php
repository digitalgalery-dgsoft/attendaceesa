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
        $tables = ['branches', 'work_locations', 'working_groups'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'region')) {
                continue;
            }

            // Normalisasi penulisan REGION-N atau Region N menjadi format baku "REGION N"
            $records = DB::table($table)
                ->whereNotNull('region')
                ->where('region', '!=', '')
                ->select('id', 'region')
                ->get();

            foreach ($records as $row) {
                $raw = trim((string)$row->region);
                if ($raw === '' || $raw === '-' || strtolower($raw) === 'null') {
                    continue;
                }

                // Ubah format REGION-4, REGION_4, Region - 4, Region 4 menjadi "REGION 4"
                $normalized = preg_replace('/([A-Za-z]+)[\s\-_]+([0-9]+)/i', '$1 $2', $raw);
                $normalized = strtoupper(preg_replace('/\s+/', ' ', trim($normalized)));

                if ($normalized !== $raw) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['region' => $normalized]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: Data normalization does not require reversion
    }
};
