<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkLocation;
use Illuminate\Support\Str;

class FixEmptyLocationCodesCommand extends Command
{
    protected $signature = 'work-locations:fix-empty-codes';
    protected $description = 'Pastikan semua work location tanpa kecuali memiliki random unique code';

    public function handle()
    {
        $existingCodes = WorkLocation::whereNotNull('code')
            ->where('code', '!=', '')
            ->pluck('code')
            ->flip()
            ->toArray();

        $emptyLocs = WorkLocation::where(function ($q) {
            $q->whereNull('code')->orWhere('code', '')->orWhere('code', 'otomatis');
        })->get();

        $count = $emptyLocs->count();
        $this->info("Ditemukan {$count} lokasi dengan code kosong.");

        foreach ($emptyLocs as $loc) {
            do {
                $code = 'LOC-' . strtoupper(Str::random(6));
            } while (isset($existingCodes[$code]));

            $existingCodes[$code] = true;
            $loc->code = $code;
            $loc->save();
            $this->info("✓ [ID: {$loc->id}] {$loc->name} -> {$code}");
        }

        $this->info("Selesai. Total work location ber-kode sekarang: " . WorkLocation::whereNotNull('code')->where('code', '!=', '')->count());
        return 0;
    }
}
