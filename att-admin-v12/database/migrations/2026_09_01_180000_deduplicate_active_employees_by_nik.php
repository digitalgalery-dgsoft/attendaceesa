<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cari seluruh NIK (employee_no) yang memiliki lebih dari 1 record dengan is_active = true
        $duplicateNiks = \Illuminate\Support\Facades\DB::table('employees')
            ->select('employee_no')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->groupBy('employee_no')
            ->havingRaw('count(*) > 1')
            ->pluck('employee_no');

        foreach ($duplicateNiks as $nik) {
            $records = \Illuminate\Support\Facades\DB::table('employees')
                ->where('employee_no', $nik)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByRaw("CASE WHEN employment_status != 'resigned' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($records->count() > 1) {
                // Record pertama dipertahankan sebagai aktif
                $primary = $records->shift();

                // Sisa record duplikat dinonaktifkan
                $duplicateIds = $records->pluck('id')->toArray();
                \Illuminate\Support\Facades\DB::table('employees')
                    ->whereIn('id', $duplicateIds)
                    ->update([
                        'is_active' => false,
                        'employment_status' => 'resigned',
                        'updated_at' => now(),
                    ]);
            }
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
