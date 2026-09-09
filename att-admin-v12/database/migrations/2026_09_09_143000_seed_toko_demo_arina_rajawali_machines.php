<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\WorkLocation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $stores = WorkLocation::whereRaw('LOWER(name) LIKE ?', ['%rajawali%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%arina rajawali%'])
                ->get();

            foreach ($stores as $s) {
                $s->update([
                    'machine_type' => 'Type Mesin 1',
                    'machine_serial_no' => 'XX-001',
                    'machines' => [
                        [
                            'machine_type' => 'Type Mesin 1',
                            'machine_serial_no' => 'XX-001',
                        ],
                        [
                            'machine_type' => 'Type Mesin 2',
                            'machine_serial_no' => 'XX-002',
                        ],
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning("Seeding Toko Demo Arina Rajawali machines failed: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
