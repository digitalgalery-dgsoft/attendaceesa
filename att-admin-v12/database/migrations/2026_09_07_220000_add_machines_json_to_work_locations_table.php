<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WorkLocation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        @ini_set('memory_limit', '1024M');

        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('work_locations', 'machines')) {
                    $table->json('machines')->nullable()->after('machine_serial_no');
                }
            });
        }

        // 1. Seed data mesin untuk Toko Demo Kalilor
        try {
            $kalilorStores = WorkLocation::whereRaw('LOWER(name) LIKE ?', ['%kalilor%'])->get();
            foreach ($kalilorStores as $kalilor) {
                $kalilor->update([
                    'machine_type' => 'Mesin D200 (Automatic Tinting)',
                    'machine_serial_no' => 'POST-2022-SUB-042',
                    'machines' => [
                        [
                            'machine_type' => 'Mesin D200 (Automatic Tinting)',
                            'machine_serial_no' => 'POST-2022-SUB-042',
                        ],
                        [
                            'machine_type' => 'Mesin Discovery (Automatic Tinting)',
                            'machine_serial_no' => 'POST-2023-SUB-089',
                        ],
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning("Seeding Toko Demo Kalilor machines failed: " . $e->getMessage());
        }

        // 2. Sinkronkan mesin historis dari daily_maintenance.sqlite (dm_raw) jika tersedia
        $sqlitePath = storage_path('app/dulux_data/daily_maintenance.sqlite');
        if (file_exists($sqlitePath)) {
            try {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $stmt = $pdo->query("SELECT store_name, sap_code, machine_type, machine_no FROM dm_raw WHERE machine_type IS NOT NULL AND machine_type != '' GROUP BY store_name, machine_type, machine_no");
                
                $storeMachines = [];
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $sName = strtoupper(trim($r['store_name'] ?? ''));
                    $mType = trim($r['machine_type'] ?? '');
                    $mNo = trim($r['machine_no'] ?? '');
                    if ($sName && ($mType || $mNo)) {
                        $storeMachines[$sName][] = [
                            'machine_type' => $mType,
                            'machine_serial_no' => $mNo,
                        ];
                    }
                }

                foreach ($storeMachines as $sName => $mList) {
                    $loc = WorkLocation::whereRaw('UPPER(TRIM(name)) = ?', [$sName])->first();
                    if ($loc && empty($loc->machines)) {
                        $loc->update([
                            'machine_type' => $mList[0]['machine_type'] ?? $loc->machine_type,
                            'machine_serial_no' => $mList[0]['machine_serial_no'] ?? $loc->machine_serial_no,
                            'machines' => $mList,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("Syncing store machines from dm_raw failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_locations')) {
            Schema::table('work_locations', function (Blueprint $table) {
                if (Schema::hasColumn('work_locations', 'machines')) {
                    $table->dropColumn('machines');
                }
            });
        }
    }
};
