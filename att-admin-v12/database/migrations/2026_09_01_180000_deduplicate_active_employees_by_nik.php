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
        // 1. Cari seluruh NIK yang memiliki lebih dari 1 record dengan is_active = true
        $duplicateNiks = Employee::select('nik')
            ->where('is_active', true)
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->groupBy('nik')
            ->havingRaw('count(*) > 1')
            ->pluck('nik');

        foreach ($duplicateNiks as $nik) {
            $records = Employee::where('nik', $nik)
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            // Record pertama dipertahankan sebagai aktif
            $primary = $records->shift();

            // Sisa record duplikat dinonaktifkan
            foreach ($records as $dup) {
                $dup->update([
                    'is_active' => false,
                    'employment_status' => 'resigned',
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
