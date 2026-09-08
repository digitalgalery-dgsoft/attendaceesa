<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Principal;
use App\Models\WorkLocation;

class VerifyWorkLocationsCommand extends Command
{
    protected $signature = 'work-locations:verify';
    protected $description = 'Verifikasi data work location dan kode acak';

    public function handle()
    {
        $company = Company::first();
        $this->info("=================================================");
        $this->info("🏢 SERVER: " . ($company ? $company->name : 'N/A'));
        $this->info("=================================================");

        $totalLocations = WorkLocation::count();
        $totalWithCode = WorkLocation::whereNotNull('code')->where('code', '!=', '')->count();
        $totalUniqueCodes = WorkLocation::whereNotNull('code')->where('code', '!=', '')->distinct('code')->count('code');

        $this->info("📊 Total Work Location di Server Ini : {$totalLocations}");
        $this->info("🔑 Total Record Ber-Kode             : {$totalWithCode}");
        $this->info("✨ Total Kode Unik (Tanpa Duplikat)  : {$totalUniqueCodes}");

        $this->info("\n--- 5 Sampel Data Work Location Terbaru ---");
        $samples = WorkLocation::with('principal', 'branch')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $headers = ['ID', 'Code', 'Principal', 'Area', 'Nama Toko / Lokasi', 'Type', 'Timezone'];
        $rows = [];
        foreach ($samples as $s) {
            $rows[] = [
                $s->id,
                $s->code,
                $s->principal?->name ?? '-',
                $s->branch?->name ?? $s->area ?? '-',
                mb_substr($s->name, 0, 40),
                $s->type,
                $s->timezone
            ];
        }

        $this->table($headers, $rows);
        $this->info("=================================================\n");
        return 0;
    }
}
