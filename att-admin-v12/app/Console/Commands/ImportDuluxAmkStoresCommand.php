<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Principal;
use App\Models\Branch;
use App\Models\WorkLocation;
use App\Models\Employee;

class ImportDuluxAmkStoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dulux:import-amk-stores {--force : Jalankan impor tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengimpor Master Data Store / Work Location Dulux AMK dari Database Sadata AMK lengkap dengan Koordinat Real, Kode SAP, Kategori Store, Type Mesin & Nomor Mesin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==========================================================================");
        $this->info("🏬 IMPORT MASTER STORE / WORK LOCATION DULUX (ICI PAINTS) - SERVER AMK");
        $this->info("==========================================================================");

        // 1. Identifikasi File Sumber
        $jsonFile = storage_path('app/dulux_data/amk_stores_2026.json');
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

        $this->info("Total data baris pemetaan di file: " . count($records));

        // 2. Identifikasi Company & Principal
        $company = Company::where('name', 'ilike', '%arina%')->first() 
            ?? Company::where('name', 'ilike', '%amk%')->first() 
            ?? Company::first();

        $principal = Principal::where('name', 'ilike', '%ici%')
            ->orWhere('name', 'ilike', '%dulux%')
            ->first();

        if (!$company) {
            $this->error("❌ Company AMK/Arina tidak ditemukan.");
            return 1;
        }

        if (!$principal) {
            $this->error("❌ Principal ICI Paints / Dulux tidak ditemukan.");
            return 1;
        }

        $this->info("Target Company  : [ID: {$company->id}] {$company->name}");
        $this->info("Target Principal: [ID: {$principal->id}] {$principal->name}");

        // 3. Cache Branch / Area
        $branches = Branch::all();
        $branchByName = [];
        foreach ($branches as $b) {
            $branchByName[strtoupper(trim($b->name))] = $b->id;
        }

        // 4. Proses Unik Toko
        $uniqueStores = [];
        $employeeMappings = [];

        foreach ($records as $item) {
            $name = trim($item['name'] ?? '');
            $sap = trim((string)($item['code'] ?? ''));
            $storeCode = trim($item['store_code'] ?? '');
            $lat = (float)($item['latitude'] ?? 0);
            $lng = (float)($item['longitude'] ?? 0);

            // Key unik toko: gunakan SAP jika ada, jika tidak gunakan store_code atau name
            $key = $sap !== '' ? "SAP_{$sap}" : ($storeCode !== '' ? "CODE_{$storeCode}" : "NAME_" . strtoupper($name));

            if (!isset($uniqueStores[$key])) {
                $uniqueStores[$key] = [
                    'name' => $name,
                    'code' => $sap ?: null,
                    'store_code' => $storeCode ?: null,
                    'area' => trim($item['area'] ?? ''),
                    'rsm_area' => trim($item['rsm_area'] ?? ''),
                    'region' => trim($item['region'] ?? ''),
                    'sub_area' => trim($item['sub_area'] ?? ''),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'category' => trim($item['category'] ?? 'Retail Store'),
                    'machine_type' => trim($item['machine_type'] ?? '') ?: null,
                    'machine_serial_no' => trim($item['machine_serial_no'] ?? '') ?: null,
                ];
            } else {
                // Update machine info if available
                if (empty($uniqueStores[$key]['machine_type']) && !empty($item['machine_type'])) {
                    $uniqueStores[$key]['machine_type'] = trim($item['machine_type']);
                }
                if (empty($uniqueStores[$key]['machine_serial_no']) && !empty($item['machine_serial_no'])) {
                    $uniqueStores[$key]['machine_serial_no'] = trim($item['machine_serial_no']);
                }
            }

            // Catat pemetaan DC
            if (!empty($item['dc_no']) || !empty($item['dc_ktp']) || !empty($item['dc_name'])) {
                $employeeMappings[] = [
                    'store_key' => $key,
                    'dc_no' => trim($item['dc_no'] ?? ''),
                    'dc_ktp' => trim($item['dc_ktp'] ?? ''),
                    'dc_name' => trim($item['dc_name'] ?? ''),
                    'area' => trim($item['area'] ?? ''),
                ];
            }
        }

        $this->info("Jumlah Master Toko Unik Teridentifikasi: " . count($uniqueStores));
        $this->info("Jumlah Pemetaan Karyawan DC: " . count($employeeMappings));

        // 5. Eksekusi Simpan WorkLocation
        $this->info("\nMemulai proses penyimpanan WorkLocation ke database...");
        DB::beginTransaction();
        try {
            $createdCount = 0;
            $updatedCount = 0;
            $locationMap = []; // key => WorkLocation model

            foreach ($uniqueStores as $key => $sData) {
                // Resolve Branch
                $branchId = null;
                $areaUpper = strtoupper($sData['area']);
                $subAreaUpper = strtoupper($sData['sub_area']);

                if (isset($branchByName[$areaUpper])) {
                    $branchId = $branchByName[$areaUpper];
                } elseif (isset($branchByName[$subAreaUpper])) {
                    $branchId = $branchByName[$subAreaUpper];
                } elseif (!empty($sData['area'])) {
                    // Buat Branch Baru jika belum ada
                    $newBranch = Branch::create([
                        'company_id' => $company->id,
                        'name' => $sData['area'],
                        'region' => $sData['region'] ?: null,
                        'is_active' => true,
                    ]);
                    $branchId = $newBranch->id;
                    $branchByName[$areaUpper] = $branchId;
                }

                // Cari lokasi yang sudah ada (berdasarkan code SAP, store_code, atau name + principal)
                $existing = WorkLocation::where('principal_id', $principal->id)
                    ->where(function ($q) use ($sData) {
                        if (!empty($sData['code'])) {
                            $q->where('code', $sData['code']);
                        }
                        if (!empty($sData['store_code'])) {
                            $q->orWhere('store_code', $sData['store_code']);
                        }
                        $q->orWhere('name', $sData['name']);
                    })
                    ->first();

                $locPayload = [
                    'company_id' => $company->id,
                    'principal_id' => $principal->id,
                    'branch_id' => $branchId,
                    'name' => $sData['name'],
                    'code' => $sData['code'],
                    'store_code' => $sData['store_code'],
                    'type' => 'client',
                    'category' => $sData['category'],
                    'machine_type' => $sData['machine_type'],
                    'machine_serial_no' => $sData['machine_serial_no'],
                    'region' => $sData['region'] ?: null,
                    'area' => $sData['area'] ?: null,
                    'sub_area' => $sData['sub_area'] ?: null,
                    'channel' => 'Retail',
                    'latitude' => $sData['latitude'],
                    'longitude' => $sData['longitude'],
                    'radius_meter' => 100,
                    'is_active' => true,
                    'status' => 'active',
                ];

                if ($existing) {
                    $existing->update($locPayload);
                    $locationMap[$key] = $existing;
                    $updatedCount++;
                } else {
                    $newLoc = WorkLocation::create($locPayload);
                    $locationMap[$key] = $newLoc;
                    $createdCount++;
                }
            }

            $this->info("  ↳ Master Toko Baru Dibuat : {$createdCount}");
            $this->info("  ↳ Master Toko Diperbarui  : {$updatedCount}");

            // 6. Sinkronisasi Karyawan DC ke WorkLocation masing-masing
            $this->info("\nMenghubungkan Karyawan DC ke WorkLocation masing-masing...");
            $empLinked = 0;

            foreach ($employeeMappings as $map) {
                $loc = $locationMap[$map['store_key']] ?? null;
                if (!$loc) continue;

                $emp = null;
                if (!empty($map['dc_no'])) {
                    $emp = Employee::where('employee_no', $map['dc_no'])->orWhere('nik', $map['dc_no'])->first();
                }
                if (!$emp && !empty($map['dc_ktp'])) {
                    $emp = Employee::where('identification_id', $map['dc_ktp'])->first();
                }
                if (!$emp && !empty($map['dc_name'])) {
                    $emp = Employee::where('name', 'ilike', $map['dc_name'])->first();
                }

                if ($emp) {
                    $emp->update([
                        'work_location_id' => $loc->id,
                        'principal_id' => $principal->id,
                        'branch_id' => $loc->branch_id ?? $emp->branch_id,
                    ]);
                    $empLinked++;
                }
            }

            $this->info("  ↳ Karyawan DC Berhasil Dihubungkan ke Toko: {$empLinked}");

            DB::commit();
            $this->info("\n🎉 SUKSES: Seluruh data Store & Work Location Dulux AMK berhasil dimigrasi & diperbarui!");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ GAGAL: Terjadi kesalahan saat mengimpor data: " . $e->getMessage());
            return 1;
        }
    }
}
