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
                            {--trigger=cron : Trigger type (cron or manual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronization of Principals and Employees from Odoo XML-RPC';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $trigger = $this->option('trigger') ?: 'cron';
        $batchId = 'SYNC-' . date('Ymd-His') . '-' . Str::random(6);

        $this->info("🚀 Starting Odoo Synchronization [Batch: {$batchId}, Trigger: {$trigger}]");

        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("❌ Company ID {$companyId} not found.");
                return self::FAILURE;
            }

            $service = OdooSyncService::fromCompany($company);
            if (!$service) {
                $this->warn("⚠️ Company [{$company->name}] does not have complete Odoo configuration. Skipping.");
                return self::SUCCESS;
            }

            $this->line("Processing Company: {$company->name}...");
            $pResult = $service->syncPrincipals($company->id);
            $eResult = $service->syncEmployees($company->id);

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Principals Created', $pResult['created'] ?? 0],
                    ['Principals Updated', $pResult['updated'] ?? 0],
                    ['Employees New', $eResult['created'] ?? 0],
                    ['Employees Updated', $eResult['updated'] ?? 0],
                    ['Employees Resigned', $eResult['resigned'] ?? 0],
                ]
            );

            return self::SUCCESS;
        }

        // Sync all configured companies
        $results = OdooSyncService::syncAllConfiguredCompanies($trigger, $batchId);

        $this->info("✅ Synchronization completed for {$results['companies_count']} company/companies.");

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
            'All Companies',
            $results['total_created'],
            $results['total_updated'],
            $results['total_resigned'],
            $results['total_employees'],
            empty($results['errors']) ? 'SUCCESS' : 'WITH ERRORS',
        ];

        $this->table(
            ['Code', 'Company', 'New', 'Update', 'Resign', 'Total Employees', 'Status'],
            $tableRows
        );

        if (!empty($results['errors'])) {
            $this->warn("⚠️ Warnings/Errors occurred during sync:");
            foreach ($results['errors'] as $err) {
                $this->line(" - $err");
            }
        }

        return self::SUCCESS;
    }
}
