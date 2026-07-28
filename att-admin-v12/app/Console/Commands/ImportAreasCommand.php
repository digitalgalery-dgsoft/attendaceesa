<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAreasCommand extends Command
{
    protected $signature = 'app:import-areas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import areas from tb_area.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = 'G:\My File\Project APlikasi Absensi\New\tb_area.csv';
        if (!file_exists($file)) {
            $this->error("File not found at $file");
            return;
        }

        $this->info("Truncating branches table...");
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\Branch::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $handle = fopen($file, "r");
        $header = fgetcsv($handle); // skip header
        
        $count = 0;
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 4) {
                \App\Models\Branch::create([
                    'code' => $data[1] ?? '',
                    'name' => $data[2] ?? '',
                    'region' => $data[3] ?? '',
                    'radius_meter' => 100,
                    'is_active' => true,
                ]);
                $count++;
            }
        }
        fclose($handle);

        $this->info("Successfully imported $count areas.");
    }
}
