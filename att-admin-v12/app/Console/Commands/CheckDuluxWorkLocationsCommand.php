<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkLocation;
use App\Models\Company;
use App\Models\Principal;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckDuluxWorkLocationsCommand extends Command
{
    protected $signature = 'dulux:check-locations';
    protected $description = 'Cek data work location, principal, company, branch, user di database';

    public function handle()
    {
        $this->info("=== DIAGNOSIS WORK LOCATIONS ===");
        
        $totalRaw = DB::table('work_locations')->count();
        $this->info("Total baris raw di table work_locations: {$totalRaw}");

        $totalModel = WorkLocation::count();
        $this->info("Total baris via Eloquent WorkLocation: {$totalModel}");

        $principals = Principal::all();
        $this->info("\n--- Data Principals ---");
        foreach ($principals as $p) {
            $c = WorkLocation::where('principal_id', $p->id)->count();
            $this->info("[ID: {$p->id}] {$p->name} -> {$c} work locations");
        }

        $companies = Company::all();
        $this->info("\n--- Data Companies ---");
        foreach ($companies as $co) {
            $c = WorkLocation::where('company_id', $co->id)->count();
            $this->info("[ID: {$co->id}] {$co->name} -> {$c} work locations");
        }

        $this->info("\n--- Sample 3 Work Locations ---");
        $samples = WorkLocation::with(['principal', 'company', 'branch'])->take(3)->get();
        foreach ($samples as $s) {
            $this->info("ID: {$s->id} | Name: {$s->name} | Code: {$s->code} | Cat: {$s->category} | Principal: " . ($s->principal ? $s->principal->name : 'NULL') . " | Company: " . ($s->company ? $s->company->name : 'NULL') . " | Branch: " . ($s->branch ? $s->branch->name : 'NULL'));
        }

        $this->info("\n--- Users & Scopes ---");
        $users = User::all();
        foreach ($users as $u) {
            $branchIds = method_exists($u, 'getAccessibleBranchIds') ? $u->getAccessibleBranchIds() : [];
            $principalIds = method_exists($u, 'getAccessiblePrincipalIds') ? $u->getAccessiblePrincipalIds() : [];
            $isSuper = method_exists($u, 'isSuperAdmin') ? ($u->isSuperAdmin() ? 'YES' : 'NO') : 'N/A';
            $this->info("User [ID: {$u->id}] {$u->name} ({$u->email}) | isSuperAdmin: {$isSuper} | BranchIds: " . json_encode($branchIds) . " | PrincipalIds: " . json_encode($principalIds));
        }

        return 0;
    }
}
