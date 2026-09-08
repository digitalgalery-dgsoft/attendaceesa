<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\Principal;
use App\Models\Branch;
use App\Models\WorkLocation;

class ImportInhouseStoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work-locations:import-inhouse {--force : Jalankan impor tanpa konfirmasi} {--clean : Hapus data work location inhouse sebelumnya}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengimpor Master Data Work Location Inhouse Store (3.513 toko/kantor) dengan Random Unique Code (LOC-XXXXXX)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==========================================================================");
        $this->info("🏬 IMPORT MASTER WORK LOCATION INHOUSE STORES (ALL PRODUCTION NODES)");
        $this->info("==========================================================================");

        // 1. Validasi File Sumber
        $jsonFile = storage_path('app/inhouse_stores.json');
        if (!file_exists($jsonFile)) {
            $this->error("❌ File data sumber tidak ditemukan di: {$jsonFile}");
            return 1;
        }

        $rawJson = file_get_contents($jsonFile);
        $records = json_decode($rawJson, true);
        if (!is_array($records) || empty($records)) {
            $this->error("❌ Data JSON kosong atau tidak valid.");
            return 1;
        }

        $totalRecords = count($records);
        $this->info("📊 Total data store inhouse di file: {$totalRecords}");

        // 2. Identifikasi Company & Principal Sesuai Server
        $company = Company::first();
        if (!$company) {
            $this->error("❌ Tidak ada data Company terdaftar di database.");
            return 1;
        }

        // Cari Principal Inhouse untuk Company ini
        $principal = Principal::where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('name', 'ilike', '%inhouse%')
                  ->orWhere('name', 'ilike', '%internal%')
                  ->orWhere('name', 'ilike', '%arina%')
                  ->orWhere('name', 'ilike', '%alva%')
                  ->orWhere('name', 'ilike', '%anugrah%')
                  ->orWhere('name', 'ilike', '%talenta%');
            })->first()
            ?? Principal::where('company_id', $company->id)->first()
            ?? Principal::where('name', 'ilike', '%inhouse%')->first()
            ?? Principal::first();

        if (!$principal) {
            $this->warn("⚠️ Principal Inhouse tidak ditemukan. Membuat principal baru...");
            $principal = Principal::create([
                'company_id' => $company->id,
                'name' => $company->name . ' (INHOUSE)',
                'code' => 'PRI-INHOUSE',
                'is_active' => true,
            ]);
        }

        $this->info("🏢 Target Company  : [ID: {$company->id}] {$company->name}");
        $this->info("🏷️ Target Principal: [ID: {$principal->id}] {$principal->name}");

        // 3. Konfirmasi jika bukan force
        if (!$this->option('force')) {
            if (!$this->confirm("Apakah Anda yakin ingin mengimpor {$totalRecords} work location ke database?", true)) {
                $this->info("Operasi dibatalkan.");
                return 0;
            }
        }

        // 4. Opsi Clean jika diminta
        if ($this->option('clean')) {
            $this->warn("🧹 Membersihkan data work location inhouse lama untuk principal ini...");
            $deleted = WorkLocation::where('company_id', $company->id)
                ->where('principal_id', $principal->id)
                ->delete();
            $this->info("✓ Berhasil menghapus {$deleted} record lama.");
        }

        // 5. Cache Branches untuk Company ini
        $branches = Branch::where('company_id', $company->id)->get();
        $branchMap = [];
        foreach ($branches as $b) {
            $branchMap[strtoupper(trim($b->name))] = $b->id;
        }

        // 6. Preload Existing Codes untuk Mencegah Tabrakan Kode Acak
        $existingCodes = WorkLocation::whereNotNull('code')->pluck('code')->flip()->toArray();

        // 7. Preload Existing Locations untuk Company ini
        $existingLocations = WorkLocation::where('company_id', $company->id)
            ->get()
            ->keyBy(fn ($loc) => strtoupper(trim($loc->name)));

        $this->info("🚀 Memulai proses sinkronisasi & insert/update data...");
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $insertedCount = 0;
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($records as $item) {
                $name = trim($item['name'] ?? '');
                if ($name === '') {
                    $bar->advance();
                    continue;
                }

                // Potong nama jika lebih dari 150 karakter (sesuai limit varchar DB)
                if (mb_strlen($name) > 150) {
                    $name = mb_substr($name, 0, 150);
                }

                $upperName = strtoupper($name);

                // Resolusi Branch / Area
                $areaName = strtoupper(trim($item['area'] ?? $item['sub_area'] ?? 'PUSAT'));
                if ($areaName === '') {
                    $areaName = 'PUSAT';
                }

                if (!isset($branchMap[$areaName])) {
                    $newBranch = Branch::create([
                        'company_id' => $company->id,
                        'name' => $areaName,
                        'code' => 'BRN-' . strtoupper(Str::random(5)),
                        'is_active' => true,
                    ]);
                    $branchMap[$areaName] = $newBranch->id;
                }
                $branchId = $branchMap[$areaName];

                // Pastikan kode acak unik
                $code = trim((string)($item['code'] ?? ''));
                if (empty($code) || $code === 'otomatis') {
                    do {
                        $code = 'LOC-' . strtoupper(Str::random(6));
                    } while (isset($existingCodes[$code]));
                } elseif (isset($existingCodes[$code])) {
                    // Jika kode sudah dipakai record lain yang namanya beda, generate kode baru
                    $existingLoc = $existingLocations->get($upperName);
                    if (!$existingLoc || $existingLoc->code !== $code) {
                        do {
                            $code = 'LOC-' . strtoupper(Str::random(6));
                        } while (isset($existingCodes[$code]));
                    }
                }
                $existingCodes[$code] = true;

                $dataPayload = [
                    'company_id' => $company->id,
                    'principal_id' => $principal->id,
                    'branch_id' => $branchId,
                    'name' => $name,
                    'address' => $item['address'] ?? null,
                    'region' => $item['region'] ?? null,
                    'area' => $item['area'] ?? null,
                    'sub_area' => $item['sub_area'] ?? null,
                    'channel' => $item['channel'] ?? null,
                    'account' => $item['account'] ?? null,
                    'timezone' => $item['timezone'] ?? 'Asia/Jakarta',
                    'type' => $item['type'] ?? 'client',
                    'latitude' => (float)($item['latitude'] ?? 0),
                    'longitude' => (float)($item['longitude'] ?? 0),
                    'radius_meter' => (int)($item['radius_meter'] ?? 100),
                    'status' => 'active',
                    'is_active' => true,
                ];

                if ($existingLocations->has($upperName)) {
                    $loc = $existingLocations->get($upperName);
                    // Pertahankan code lama jika sudah ada, atau isi dengan code acak baru
                    if (empty($loc->code)) {
                        $dataPayload['code'] = $code;
                    }
                    $loc->update($dataPayload);
                    $updatedCount++;
                } else {
                    $dataPayload['code'] = $code;
                    $newLoc = WorkLocation::create($dataPayload);
                    $existingLocations->put($upperName, $newLoc);
                    $insertedCount++;
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $this->info("==========================================================================");
            $this->info("🎉 IMPORT SELESAI DENGAN SUKSES!");
            $this->info("==========================================================================");
            $this->info("📥 Record Baru Diinsert : {$insertedCount}");
            $this->info("🔄 Record Diperbarui    : {$updatedCount}");
            $totalInDb = WorkLocation::where('company_id', $company->id)->count();
            $this->info("📊 Total Work Location di DB Server Ini: {$totalInDb}");
            $this->info("==========================================================================");

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine(2);
            $this->error("❌ Gagal melakukan import data: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
