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
        $this->info("=== DIAGNOSIS ENVIRONMENT & WORK LOCATIONS ===");
        $this->info("Base Path: " . base_path());
        $this->info("DB Default: " . config('database.default'));
        $this->info("DB Config: " . json_encode(config('database.connections.' . config('database.default'))));
        
        $totalRaw = DB::table('work_locations')->count();
        $this->info("Total baris raw di table work_locations: {$totalRaw}");

        $totalModel = WorkLocation::count();
        $this->info("Total baris via Eloquent WorkLocation: {$totalModel}");

        // Cek direktori lain di /www/wwwroot
        $this->info("\n--- Cek Direktori di /www/wwwroot ---");
        if (is_dir('/www/wwwroot')) {
            $dirs = glob('/www/wwwroot/*', GLOB_ONLYDIR);
            foreach ($dirs as $d) {
                $envFile = $d . '/.env';
                $dbInfo = 'No .env';
                if (file_exists($envFile)) {
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $dbVals = [];
                    foreach ($lines as $l) {
                        if (preg_match('/^(DB_DATABASE|DB_USERNAME|DB_CONNECTION|DB_HOST)=(.*)$/', trim($l), $m)) {
                            $dbVals[$m[1]] = trim($m[2]);
                        }
                    }
                    $dbInfo = json_encode($dbVals);
                }
                $this->info("Dir: {$d} => {$dbInfo}");
            }
        }

        // Cek Nginx Vhosts
        $this->info("\n--- Cek Nginx Vhost for amk.esa-solutions.id ---");
        if (is_dir('/www/server/panel/vhost/nginx')) {
            $vhosts = glob('/www/server/panel/vhost/nginx/*.conf');
            foreach ($vhosts as $vf) {
                $content = file_get_contents($vf);
                if (str_contains($content, 'amk.esa-solutions.id') || str_contains($content, 'esa-solutions.id')) {
                    $this->info("VHost: {$vf}");
                    if (preg_match('/root\s+([^;]+);/', $content, $rm)) {
                        $this->info("  ↳ root: " . trim($rm[1]));
                    }
                }
            }
        }

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

        return 0;
    }
}
