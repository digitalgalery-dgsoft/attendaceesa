<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Principal;
use App\Models\WorkLocation;

class CleanDuluxWorkLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dulux:clean-work-locations {--force : Eksekusi penghapusan langsung tanpa konfirmasi} {--dry-run : Hanya tampilkan jumlah data yang akan dihapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan master WorkLocation duplikat/dummy koordinat khusus prinsiple PT ICI PAINTS INDONESIA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("================================================================");
        $this->info("🧹 PEMBERSIHAN MASTER WORK LOCATION KHUSUS PRINSIPLE ICI PAINTS");
        $this->info("================================================================");

        // 1. Identifikasi Principal ICI / Dulux
        $principals = Principal::where(function ($q) {
            $q->where('name', 'ilike', '%ici%')
              ->orWhere('name', 'ilike', '%dulux%')
              ->orWhere('code', 'ilike', '%ici%')
              ->orWhere('code', 'ilike', '%dulux%');
        })->get();

        if ($principals->isEmpty()) {
            $this->warn("⚠️ Tidak ditemukan data Principal dengan nama/kode mengandung 'ICI' atau 'Dulux'.");
            return 0;
        }

        $principalIds = $principals->pluck('id')->toArray();
        $this->info("Ditemukan " . count($principals) . " Principal terkait:");
        foreach ($principals as $p) {
            $this->line("  - [ID: {$p->id}] {$p->name} ({$p->code})");
        }

        // 2. Query Work Locations yang terhubung ke principal ini
        $query = WorkLocation::whereIn('principal_id', $principalIds);
        $totalLocations = $query->count();

        $this->info("\nTotal Work Locations terdaftar untuk Principal ini: " . number_format($totalLocations));

        if ($totalLocations === 0) {
            $this->info("✅ Tidak ada Work Locations untuk dibersihkan.");
            return 0;
        }

        $locationIds = $query->pluck('id')->toArray();

        // 3. Cek Relasi Database yang terhubung
        $this->info("\n--- Pengecekan Relasi Data Terhubung ---");
        
        $relCounts = [];
        $tablesToCheck = [
            'employees' => 'work_location_id',
            'employee_schedules' => 'work_location_id',
            'itinerary_items' => 'work_location_id',
            'report_submissions' => 'work_location_id',
            'location_requests' => 'work_location_id',
            'bap_requests' => 'work_location_id',
            'meetings' => 'work_location_id',
            'attendances' => 'work_location_id',
        ];

        foreach ($tablesToCheck as $tbl => $col) {
            try {
                if (DB::getSchemaBuilder()->hasTable($tbl) && DB::getSchemaBuilder()->hasColumn($tbl, $col)) {
                    $cnt = DB::table($tbl)->whereIn($col, $locationIds)->count();
                    $relCounts[$tbl] = $cnt;
                    $this->line("  - Tabel `{$tbl}`: {$cnt} baris terhubung");
                }
            } catch (\Exception $e) {
                // Ignore schema check errors
            }
        }

        if ($this->option('dry-run')) {
            $this->warn("\n[DRY RUN AKTIF] Tidak ada data yang dihapus.");
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("\nApakah Anda yakin ingin menghapus {$totalLocations} Work Locations untuk ICI Paints? Tindakan ini akan mengosongkan work_location_id pada tabel relasi terkait.")) {
                $this->warn("Operasi dibatalkan oleh pengguna.");
                return 0;
            }
        }

        // 4. Eksekusi Pembersihan Relasi & Penghapusan dalam Transaksi DB
        $this->info("\nMemulai proses pembersihan...");
        DB::beginTransaction();
        try {
            foreach ($tablesToCheck as $tbl => $col) {
                if (isset($relCounts[$tbl]) && $relCounts[$tbl] > 0) {
                    DB::table($tbl)->whereIn($col, $locationIds)->update([$col => null]);
                    $this->line("  ↳ Berhasil meng-unlink {$relCounts[$tbl]} referensi pada `{$tbl}`.");
                }
            }

            // Hapus WorkLocation
            $deletedCount = WorkLocation::whereIn('id', $locationIds)->delete();
            DB::commit();

            $this->info("\n✅ SUKSES: Berhasil menghapus {$deletedCount} Work Locations milik PT ICI PAINTS INDONESIA.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ GAGAL: Terjadi kesalahan saat menghapus data: " . $e->getMessage());
            return 1;
        }
    }
}
