<?php

namespace App\Console\Commands;

use App\Models\Principal;
use App\Models\Product;
use App\Models\ReportTemplate;
use Illuminate\Console\Command;

class CheckDuluxProductsCommand extends Command
{
    protected $signature = 'dulux:check-products {--fix : Paksa sync ulang 69 produk ke template}';
    protected $description = 'Periksa status 69 produk Dulux dan relasi ke template';

    public function handle(): int
    {
        $this->info("Checking Dulux Products in Database...");
        $defaultConn = config('database.default');
        $connConfig = config("database.connections.{$defaultConn}");
        $this->line("DB Connection: default={$defaultConn}, host=" . ($connConfig['host'] ?? 'none') . ", db=" . ($connConfig['database'] ?? 'none') . ", user=" . ($connConfig['username'] ?? 'none'));

        $dulux = Principal::where('code', 'PR-ICI-PAINTS')
            ->orWhere('name', 'LIKE', '%ICI PAINTS%')
            ->orWhere('name', 'LIKE', '%DULUX%')
            ->orWhere('subdomain', 'dulux')
            ->first();

        if (!$dulux) {
            $this->error("Principal Dulux not found!");
            return 1;
        }

        $this->line("Dulux Principal: ID={$dulux->id}, Name={$dulux->name}");

        $allDuluxProducts = Product::where('principal_id', $dulux->id)->get();
        $this->line("Total Products with principal_id={$dulux->id}: " . $allDuluxProducts->count());
        $sampleProd = Product::where('sku_code', 'DLX-CATYLAC-CEILING')->first();
        if ($sampleProd) {
            $this->line("Sample DLX-CATYLAC-CEILING description: '{$sampleProd->description}'");
        }

        $demoSkus = ['DLX-WTS-WHT-25L', 'DLX-CTL-INT-5KG', 'DLX-ECL-ANT-25L', 'DLX-AQS-ABU-4KG', 'DLX-PNT-ALM-25L'];
        $foundDemo = Product::withTrashed()->whereIn('sku_code', $demoSkus)->get();
        $this->line("Demo products found: " . $foundDemo->count());
        foreach ($foundDemo as $d) {
            $this->line(" - Demo: {$d->sku_code} ({$d->name}), deleted_at=" . ($d->deleted_at ?? 'null'));
        }

        $allTemplates = ReportTemplate::where("code", "LIKE", "%DULUX%")
            ->orWhere("title", "LIKE", "%DULUX%")
            ->orWhereIn("id", [31, 38])
            ->get();
        $this->line("Dulux templates (by code/title/id) found: " . $allTemplates->count());
        foreach ($allTemplates as $t) {
            $this->line(" - Template ID={$t->id} [{$t->code}] title='{$t->title}' active=" . ($t->is_active ? '1' : '0') . " pid={$t->principal_id} : {$t->products()->count()} products linked");
        }

        // Check JSON descriptions
        $jsonDescCount = \Illuminate\Support\Facades\DB::table('products')->where('description', 'LIKE', '{%')->count();
        $this->line("Products with raw JSON in description: {$jsonDescCount}");

        if ($jsonDescCount > 0) {
            $this->info("Converting raw JSON descriptions to clean text...");
            $prods = \Illuminate\Support\Facades\DB::table('products')->where('description', 'LIKE', '{%')->get();
            foreach ($prods as $p) {
                $clean = Product::formatDescriptionText($p->description);
                if ($clean && $clean !== $p->description) {
                    \Illuminate\Support\Facades\DB::table('products')->where('id', $p->id)->update(['description' => $clean]);
                }
            }
            $remaining = \Illuminate\Support\Facades\DB::table('products')->where('description', 'LIKE', '{%')->count();
            $this->line("JSON descriptions converted! Remaining JSON: {$remaining}");
        }

        if ($this->option('fix')) {
            $this->info("Running FIX: Re-syncing Dulux products to templates...");
            $migration = require database_path('migrations/2026_09_09_150000_update_ici_paint_products_from_excel.php');
            $migration->up();

            $this->info("Running FIX: Syncing Dulux Offtake template fields...");
            ReportTemplate::syncDuluxOfftakeTemplate();

            $this->info("Running FIX: Syncing Dulux Stock End template fields and healing submissions...");
            ReportTemplate::syncDuluxMergedStockEnd();

            $this->info("FIX complete! Re-checking...");
            foreach ($allTemplates as $t) {
                $t->refresh();
                $this->line(" - After Fix [{$t->code}]: {$t->products()->count()} products linked");
            }
        }

        return 0;
    }
}
