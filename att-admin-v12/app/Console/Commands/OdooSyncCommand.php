<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\OdooSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OdooSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odoo:sync 
                            {--company= : Sync specific Company ID} 
                            {--trigger=cron : Trigger type (cron or manual)}
                            {--chunk=250 : Batch chunk size}
                            {--silent : Run without verbose itemized output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronization of Principals and Employees from Odoo XML-RPC (Newest First)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $trigger = $this->option('trigger') ?: 'cron';
        $isSilent = (bool) $this->option('silent');
        $batchId = 'SYNC-' . date('Ymd-His') . '-' . Str::random(6);

        $this->info("================================================================================");
        $this->info("🚀 MEMULAI SINKRONISASI ODOO [Batch: {$batchId}, Trigger: {$trigger}]");
        $this->info("📅 Waktu Eksekusi: " . date('Y-m-d H:i:s T') . " (Urutan: Data Terbaru / Write Date Desc)");
        $this->info("================================================================================");

        // Progress callback to format output identically to manual stream sync
        $progressCallback = function (string $type, string $message, ?array $meta = null) use ($isSilent) {
            if ($isSilent && in_array($type, ['created', 'updated', 'resigned', 'info'])) {
                return;
            }

            $time = date('H:i:s');
            switch ($type) {
                case 'created':
                    $this->line("<fg=green>[{$time}] {$message}</>");
                    break;
                case 'updated':
                    $this->line("<fg=yellow>[{$time}] {$message}</>");
                    break;
                case 'resigned':
                    $this->line("<fg=magenta>[{$time}] {$message}</>");
                    break;
                case 'batch':
                case 'company_start':
                    $this->line("<fg=cyan;options=bold>[{$time}] {$message}</>");
                    break;
                case 'progress':
                case 'summary':
                    $this->line("<fg=blue;options=bold>[{$time}] {$message}</>");
                    break;
                case 'error':
                    $this->line("<fg=red;options=bold>[{$time}] {$message}</>");
                    break;
                case 'warning':
                    $this->line("<fg=yellow;options=bold>[{$time}] {$message}</>");
                    break;
                default:
                    $this->line("[{$time}] {$message}");
                    break;
            }
        };

        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("❌ Company ID {$companyId} tidak ditemukan di database lokal.");
                return self::FAILURE;
            }

            $service = OdooSyncService::fromCompany($company);
            if (!$service) {
                $this->warn("⚠️ Company [{$company->name}] belum memiliki konfigurasi Odoo lengkap. Dilewati.");
                return self::SUCCESS;
            }

            $this->info("🏢 Memproses Perusahaan Tunggal: {$company->name} (DB: {$company->odoo_db})...");
            $pResult = $service->syncPrincipals($company->id, null, $progressCallback);
            $eResult = $service->syncEmployees($company->id, null, $progressCallback);

            $this->newLine();
            $this->table(
                ['Metrik Sinkronisasi', 'Jumlah Data'],
                [
                    ['Principals Baru', $pResult['created'] ?? 0],
                    ['Principals Diperbarui', $pResult['updated'] ?? 0],
                    ['Karyawan Baru (Aktif)', $eResult['created'] ?? 0],
                    ['Karyawan Diperbarui', $eResult['updated'] ?? 0],
                    ['Karyawan Resign', $eResult['resigned'] ?? 0],
                    ['Error / Peringatan', count(array_merge($pResult['errors'] ?? [], $eResult['errors'] ?? []))],
                ]
            );

            return self::SUCCESS;
        }

        // Sync all configured companies
        $results = OdooSyncService::syncAllConfiguredCompanies($trigger, $batchId, $progressCallback);

        $this->newLine();
        $this->info("================================================================================");
        $this->info("✅ SINKRONISASI SELESAI UNTUK {$results['companies_count']} PERUSAHAAN");
        $this->info("================================================================================");

        $tableRows = [];
        foreach ($results['companies'] as $c) {
            $tableRows[] = [
                $c['company_code'] ?? '-',
                $c['company_name'],
                $c['created'],
                $c['updated'],
                $c['resigned'],
                $c['total_employees'],
                $c['status'],
            ];
        }

        $tableRows[] = [
            'TOTAL',
            'Seluruh Perusahaan',
            $results['total_created'],
            $results['total_updated'],
            $results['total_resigned'],
            $results['total_employees'],
            empty($results['errors']) ? 'SUCCESS' : 'WITH ERRORS',
        ];

        $this->table(
            ['Kode', 'Perusahaan', 'Baru', 'Update', 'Resign', 'Total Karyawan Aktif', 'Status'],
            $tableRows
        );

        if (!empty($results['errors'])) {
            $this->warn("⚠️ Catatan / Peringatan selama proses sinkronisasi:");
            foreach ($results['errors'] as $err) {
                $this->line(" - $err");
            }
        }

        return self::SUCCESS;
    }
}
