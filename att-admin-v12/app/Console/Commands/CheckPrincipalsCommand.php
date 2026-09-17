<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Principal;
use Illuminate\Console\Command;

class CheckPrincipalsCommand extends Command
{
    protected $signature = 'app:check-principals {keyword=ANUGRAH}';
    protected $description = 'Check Principals and Companies matching keyword';

    public function handle(): int
    {
        $keyword = $this->argument('keyword');
        $this->info("=== CHECKING PRINCIPALS & COMPANIES FOR '{$keyword}' ===");

        $this->info("--- COMPANIES ---");
        $companies = Company::where('name', 'ILIKE', "%{$keyword}%")
            ->orWhere('name', 'ILIKE', '%TERPERCAYA%')
            ->get(['id', 'name']);
        foreach ($companies as $c) {
            $this->line("Company ID: {$c->id} | Name: {$c->name}");
        }

        $this->info("--- ALL PRINCIPALS (COUNT: " . Principal::count() . ") ---");
        $principals = Principal::where('name', 'ILIKE', "%{$keyword}%")
            ->orWhere('name', 'ILIKE', '%TERPERCAYA%')
            ->get(['id', 'name', 'code', 'company_id', 'is_active']);
        foreach ($principals as $p) {
            $companyName = $p->company ? $p->company->name : 'NO COMPANY';
            $active = $p->is_active ? 'ACTIVE' : 'INACTIVE';
            $this->line("Principal ID: {$p->id} | Name: {$p->name} | Code: {$p->code} | Company: {$companyName} ({$p->company_id}) | Status: {$active}");
        }

        // Also check principals belonging to any company matching keyword
        if ($companies->isNotEmpty()) {
            $this->info("--- PRINCIPALS UNDER MATCHING COMPANIES ---");
            $companyIds = $companies->pluck('id');
            $childPrincipals = Principal::whereIn('company_id', $companyIds)->get(['id', 'name', 'code', 'company_id', 'is_active']);
            foreach ($childPrincipals as $cp) {
                $active = $cp->is_active ? 'ACTIVE' : 'INACTIVE';
                $this->line("Under Company {$cp->company_id}: [{$cp->id}] {$cp->name} (Status: {$active})");
            }
        }

        return 0;
    }
}
