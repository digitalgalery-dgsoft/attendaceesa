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

        $this->info("--- CHECKING EMPLOYEES UNDER COMPANY 1 & 3 ---");
        $empComp1 = \App\Models\Employee::where('company_id', 1)->count();
        $empComp3 = \App\Models\Employee::where('company_id', 3)->count();
        $this->line("Employees with company_id = 1 (PT ANUGRAH TERPERCAYA KERJA): {$empComp1}");
        $this->line("Employees with company_id = 3 (PT ANUGRAH TALENTA BERKARYA): {$empComp3}");

        $empP37 = \App\Models\Employee::where('principal_id', 37)->count();
        $empP42 = \App\Models\Employee::where('principal_id', 42)->count();
        $this->line("Employees with principal_id = 37 (PT ANUGRAH TALENTA BERKARYA): {$empP37}");
        $this->line("Employees with principal_id = 42 (PT ANUGRAH TALENTA BERKARYA): {$empP42}");

        // Distinct principals of employees under company 1
        $distinctPrincipalsComp1 = \App\Models\Employee::where('company_id', 1)
            ->select('principal_id', \DB::raw('count(*) as count'))
            ->groupBy('principal_id')
            ->get();
        $this->line("Principal IDs of employees under company 1: " . json_encode($distinctPrincipalsComp1->toArray()));

        // Also check if any employee has principal_id null or name matching
        $nullPrincipalCount = \App\Models\Employee::where('company_id', 1)->whereNull('principal_id')->count();
        $this->line("Employees under company 1 with principal_id IS NULL: {$nullPrincipalCount}");

        return 0;
    }
}
