<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cari semua submission yang berakhiran -1 (hasil split multi-produk kemarin)
        $splitSubs = DB::table('report_submissions')
            ->where('submission_code', 'LIKE', 'RPT-%-1')
            ->get();

        foreach ($splitSubs as $sub1) {
            $baseCode = substr($sub1->submission_code, 0, -2); // buang '-1'

            // Ambil values dari submission -1
            $values = DB::table('report_submission_values')
                ->where('report_submission_id', $sub1->id)
                ->get();

            // Cek offtake_items_json
            $itemsJsonRow = $values->firstWhere('field_name', 'offtake_items_json');
            $items = null;
            if ($itemsJsonRow) {
                $raw = $itemsJsonRow->value_json ?? $itemsJsonRow->value_text;
                $items = is_array($raw) ? $raw : json_decode((string)$raw, true);
            }

            if (!empty($items) && is_array($items)) {
                $totUnit = 0;
                $totLiter = 0;
                $totRp = 0;
                $productNames = [];
                $allBrands = [];

                foreach ($items as $it) {
                    $qT = (float)($it['qty_tin'] ?? 0);
                    $qG = (float)($it['qty_galon'] ?? 0);
                    $qP = (float)($it['qty_pail'] ?? 0);
                    $totUnit += (int)($it['total_unit'] ?? ($qT + $qG + $qP));
                    $totLiter += (float)($it['total_liter'] ?? (($it['volume_tin_l'] ?? 0) + ($it['volume_galon_l'] ?? 0) + ($it['volume_pail_l'] ?? 0)));
                    $totRp += (float)($it['total_nilai_rp'] ?? 0);

                    $pName = $it['sub_brand'] ?? ($it['product_name'] ?? null);
                    if ($pName && !in_array($pName, $productNames)) {
                        $productNames[] = $pName;
                    }
                    $b = $it['brand'] ?? 'Dulux';
                    if ($b && !in_array($b, $allBrands)) {
                        $allBrands[] = $b;
                    }
                }

                // Update values pada submission utama
                // 1. total_volume_unit
                DB::table('report_submission_values')
                    ->where('report_submission_id', $sub1->id)
                    ->where('field_name', 'total_volume_unit')
                    ->update([
                        'value_number' => $totUnit,
                        'value_text' => (string)$totUnit,
                    ]);

                // 2. total_volume_liter
                DB::table('report_submission_values')
                    ->where('report_submission_id', $sub1->id)
                    ->where('field_name', 'total_volume_liter')
                    ->update([
                        'value_number' => $totLiter,
                        'value_text' => (string)$totLiter,
                    ]);

                // 3. total_nilai_sales_rp
                DB::table('report_submission_values')
                    ->where('report_submission_id', $sub1->id)
                    ->where('field_name', 'total_nilai_sales_rp')
                    ->update([
                        'value_number' => $totRp,
                        'value_text' => 'Rp ' . number_format($totRp, 0, ',', '.'),
                    ]);

                // 4. sub_brand / subbrand / produk_terjual
                if (!empty($productNames)) {
                    $summaryProducts = implode(', ', $productNames);
                    DB::table('report_submission_values')
                        ->where('report_submission_id', $sub1->id)
                        ->whereIn('field_name', ['sub_brand', 'subbrand', 'sub_brand_produk', 'subbrand_produk', 'produk_terjual'])
                        ->update([
                            'value_text' => $summaryProducts,
                        ]);
                }

                // 5. brand
                if (!empty($allBrands)) {
                    $summaryBrand = count($allBrands) === 1 ? $allBrands[0] : implode(', ', $allBrands);
                    DB::table('report_submission_values')
                        ->where('report_submission_id', $sub1->id)
                        ->where('field_name', 'brand')
                        ->update([
                            'value_text' => $summaryBrand,
                        ]);
                }
            }

            // Ganti nama kode submission -1 menjadi baseCode jika belum ada yang memakai
            $baseExists = DB::table('report_submissions')->where('submission_code', $baseCode)->exists();
            if (!$baseExists) {
                DB::table('report_submissions')
                    ->where('id', $sub1->id)
                    ->update(['submission_code' => $baseCode]);
            }

            // Hapus duplikat split submission (-2, -3, dst) yang berakar dari baseCode ini
            $duplicates = DB::table('report_submissions')
                ->where('submission_code', 'LIKE', $baseCode . '-%')
                ->where('id', '!=', $sub1->id)
                ->get();

            foreach ($duplicates as $dup) {
                DB::table('report_submission_values')->where('report_submission_id', $dup->id)->delete();
                DB::table('report_submissions')->where('id', $dup->id)->delete();
            }
        }

        // Pembersihan tambahan jika masih ada sisa orphan RPT-%-2, RPT-%-3
        for ($i = 2; $i <= 9; $i++) {
            $orphanSubs = DB::table('report_submissions')
                ->where('submission_code', 'LIKE', "RPT-%-{$i}")
                ->get();

            foreach ($orphanSubs as $orphan) {
                $base = substr($orphan->submission_code, 0, -2);
                $hasParent = DB::table('report_submissions')
                    ->where(function ($q) use ($base) {
                        $q->where('submission_code', $base)
                          ->orWhere('submission_code', $base . '-1');
                    })->exists();

                if ($hasParent) {
                    DB::table('report_submission_values')->where('report_submission_id', $orphan->id)->delete();
                    DB::table('report_submissions')->where('id', $orphan->id)->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as this is data consolidation
    }
};
