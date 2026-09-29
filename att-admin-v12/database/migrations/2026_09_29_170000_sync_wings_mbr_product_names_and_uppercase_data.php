<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Synchronize historical Wings MBR submission items with latest master product names,
     * and uppercase all employee names, store names, and product names.
     */
    public function up(): void
    {
        // 1. Preload all active master products
        $products = Product::all();
        $productsById = [];
        $productsBySku = [];

        foreach ($products as $p) {
            $productsById[$p->id] = $p;
            if (!empty($p->sku_code)) {
                $cleanSku = preg_replace('/\s+/', ' ', strtoupper(trim((string)$p->sku_code)));
                $productsBySku[$cleanSku] = $p;
            }
        }

        // 2. Synchronize ReportSubmissionValue for Wings cart items
        $submissionValues = DB::table('report_submission_values')
            ->whereIn('field_name', [
                'mbr_freetaste_items_json',
                'mbr_sampling_items_json',
                'mbr_sales_items_json'
            ])
            ->get();

        foreach ($submissionValues as $val) {
            $raw = is_array($val->value_json) 
                ? $val->value_json 
                : (is_string($val->value_text) ? json_decode($val->value_text, true) : null);

            if (!is_array($raw) || empty($raw)) {
                continue;
            }

            $modified = false;
            foreach ($raw as &$it) {
                $pId = $it['product_id'] ?? ($it['id'] ?? null);
                $sku = !empty($it['sku_code'] ?? ($it['sku'] ?? '')) 
                    ? preg_replace('/\s+/', ' ', strtoupper(trim((string)($it['sku_code'] ?? $it['sku'])))) 
                    : '';

                $mProd = null;
                if ($pId && isset($productsById[$pId])) {
                    $mProd = $productsById[$pId];
                }
                if (!$mProd && $sku !== '' && isset($productsBySku[$sku])) {
                    $mProd = $productsBySku[$sku];
                }

                if ($mProd) {
                    $latestName = strtoupper(trim((string)$mProd->name));
                    $latestSku = !empty($mProd->sku_code) ? strtoupper(trim((string)$mProd->sku_code)) : ($sku ?: '-');

                    if (($it['name'] ?? '') !== $latestName || ($it['product_name'] ?? '') !== $latestName) {
                        $it['name'] = $latestName;
                        $it['product_name'] = $latestName;
                        $it['sku_code'] = $latestSku;
                        $it['sku'] = $latestSku;
                        $modified = true;
                    }
                } else {
                    $currentName = strtoupper(trim((string)($it['name'] ?? ($it['product_name'] ?? ($it['nama_produk'] ?? 'PRODUK')))));
                    if (($it['name'] ?? '') !== $currentName) {
                        $it['name'] = $currentName;
                        $it['product_name'] = $currentName;
                        $modified = true;
                    }
                }
            }
            unset($it);

            if ($modified) {
                $encoded = json_encode($raw);
                DB::table('report_submission_values')
                    ->where('id', $val->id)
                    ->update([
                        'value_text' => $encoded,
                        'value_json' => $encoded,
                        'updated_at' => now(),
                    ]);
            }
        }

        // 3. Uppercase store_name in report_submissions if set
        try {
            DB::table('report_submissions')
                ->whereNotNull('store_name')
                ->where('store_name', '!=', '')
                ->update([
                    'store_name' => DB::raw('UPPER(store_name)'),
                ]);
        } catch (\Throwable $e) {
            // Ignore if DB dialect differs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data formatting migration
    }
};
