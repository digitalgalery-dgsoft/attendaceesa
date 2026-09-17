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
        $companies = Company::whereRaw('LOWER(name) LIKE ?', ["%".strtolower($keyword)."%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%terpercaya%'])
            ->get(['id', 'name']);
        foreach ($companies as $c) {
            $this->line("Company ID: {$c->id} | Name: {$c->name}");
        }

        $this->info("--- ALL PRINCIPALS (COUNT: " . Principal::count() . ") ---");
        $principals = Principal::whereRaw('LOWER(name) LIKE ?', ["%".strtolower($keyword)."%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%terpercaya%'])
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

        $allComps = Company::all();
        foreach ($allComps as $comp) {
            $this->line("Comp ID: {$comp->id} | Name: {$comp->name} | Code: {$comp->code} | Odoo URL: {$comp->odoo_url} | Odoo DB: {$comp->odoo_db} | Odoo User: {$comp->odoo_username}");
        }

        $c1 = Company::find(1);
        if ($c1) {
            $this->line("Company 1: ID={$c1->id}, Name={$c1->name}, Code={$c1->code}, Created={$c1->created_at}");
        }

        $inhouseDepts = \App\Models\Department::whereRaw('LOWER(name) LIKE ?', ['%inhouse%'])->get(['id', 'name']);
        $this->line("Inhouse depts: " . json_encode($inhouseDepts->toArray()));
        if ($inhouseDepts->isNotEmpty()) {
            $inhouseEmps = \App\Models\Employee::where('company_id', 1)->whereIn('department_id', $inhouseDepts->pluck('id'))->count();
            $this->line("Employees in Company 1 with Inhouse Dept: {$inhouseEmps}");

            $inhouseDist = \App\Models\Employee::where('company_id', 1)
                ->whereIn('department_id', $inhouseDepts->pluck('id'))
                ->select('principal_id', \DB::raw('count(*) as count'))
                ->groupBy('principal_id')
                ->get();
            $this->line("Inhouse employee principal breakdown: " . json_encode($inhouseDist->toArray()));
        }

        return 0;
    }
}
