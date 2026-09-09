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

        $demoSkus = ['DLX-WTS-WHT-25L', 'DLX-CTL-INT-5KG', 'DLX-ECL-ANT-25L', 'DLX-AQS-ABU-4KG', 'DLX-PNT-ALM-25L'];
        $foundDemo = Product::withTrashed()->whereIn('sku_code', $demoSkus)->get();
        $this->line("Demo products found: " . $foundDemo->count());
        foreach ($foundDemo as $d) {
            $this->line(" - Demo: {$d->sku_code} ({$d->name}), deleted_at=" . ($d->deleted_at ?? 'null'));
        }

        $duluxTemplates = ReportTemplate::where("code", "LIKE", "%DULUX%")->get();
        $this->line("Dulux templates found: " . $duluxTemplates->count());
        foreach ($duluxTemplates as $t) {
            $this->line(" - Template ID={$t->id} [{$t->code}] active=" . ($t->is_active ? '1' : '0') . " pid={$t->principal_id} : {$t->products()->count()} products linked");
        }

        if ($this->option('fix')) {
            $this->info("Running FIX: Re-syncing Dulux products to templates...");
            $migration = require database_path('migrations/2026_09_09_150000_update_ici_paint_products_from_excel.php');
            $migration->up();
            $this->info("FIX complete! Re-checking...");
            foreach ($duluxTemplates as $t) {
                $t->refresh();
                $this->line(" - After Fix [{$t->code}]: {$t->products()->count()} products linked");
            }
        }

        return 0;
    }
}
