<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\TrackingHistory;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mengoreksi data riwayat tracking yang sebelumnya tersimpan dalam format UTC (selisih -7 jam dari WIB)
        // Contoh: jam 01:31 -> 08:31 WIB, jam 02:17 -> 09:17 WIB, jam 03:25 -> 10:25 WIB
        $records = TrackingHistory::all();
        foreach ($records as $record) {
            $created = Carbon::parse($record->created_at);
            if ($created->hour < 7) {
                $adjusted = $created->copy()->addHours(7);
                $record->created_at = $adjusted;
                $record->updated_at = $adjusted;
                $record->saveQuietly();
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
