<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ReportSubmissionValue;
use App\Models\ReportSubmission;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update multi-item OOS JSON records (oos_items_json)
        $jsonRows = ReportSubmissionValue::where('field_name', 'oos_items_json')->get();
        foreach ($jsonRows as $row) {
            $raw = $row->value_json ?? $row->value_text;
            if (is_string($raw)) {
                $items = json_decode($raw, true);
            } elseif (is_array($raw)) {
                $items = $raw;
            } else {
                continue;
            }

            if (!is_array($items) || empty($items)) {
                continue;
            }

            $changed = false;
            foreach ($items as &$item) {
                $isNoOos = !empty($item['is_no_oos']) 
                    || !empty($item['is_complete']) 
                    || stripos((string)($item['product_name'] ?? ''), 'No OOS') !== false
                    || stripos((string)($item['alasan_oos'] ?? ''), 'Stok Lengkap') !== false;

                if (!$isNoOos) {
                    $curLama = isset($item['lama_oos_hari']) ? (int)$item['lama_oos_hari'] : 0;
                    if ($curLama <= 0) {
                        $item['lama_oos_hari'] = 1;
                        $changed = true;
                    }
                }
            }
            unset($item);

            if ($changed) {
                $row->value_json = $items;
                $row->value_text = json_encode($items, JSON_UNESCAPED_UNICODE);
                $row->save();
            }
        }

        // 2. Update direct field lama_oos_hari and aliases for non-NoOOS submissions
        $targetFields = [
            'lama_oos_hari',
            'lama_kondisi_barang_kosong_jumlah_hari',
            'lama_kondisi_oos_jumlah_hari',
            'lama_hari_oos',
        ];

        $directRows = ReportSubmissionValue::whereIn('field_name', $targetFields)
            ->where(function ($q) {
                $q->where('value_number', '<=', 0)
                  ->orWhereNull('value_number')
                  ->orWhere('value_text', '0');
            })
            ->get();

        foreach ($directRows as $row) {
            // Check peer values in same submission
            $subValues = ReportSubmissionValue::where('report_submission_id', $row->report_submission_id)
                ->pluck('value_text', 'field_name');

            $tipe = strtolower(trim((string)($subValues['tipe_laporan_oos'] ?? '')));
            $alasan = strtolower(trim((string)($subValues['penyebab_alasan_out_of_stock_oos'] ?? ($subValues['alasan_oos'] ?? ''))));
            $produk = strtolower(trim((string)($subValues['pilih_produk_dulux_yang_mengalami_out_of_stock_oos'] ?? ($subValues['nama_produk_yang_kosong_oos'] ?? ''))));

            $isNoOos = ($tipe === 'no_oos' || str_contains($alasan, 'no oos') || str_contains($alasan, 'stok lengkap') || str_contains($produk, 'no oos'));

            if (!$isNoOos) {
                $row->value_number = 1.00;
                $row->value_text = '1';
                $row->save();
            }
        }

        // 3. Update SQLite benchmark oos_raw if present
        try {
            $sqlitePath = database_path('dulux_reporting.sqlite');
            if (file_exists($sqlitePath)) {
                $pdo = new \PDO("sqlite:" . $sqlitePath);
                $pdo->exec("UPDATE oos_raw SET lama_oos_hari = 1 WHERE is_oos = 1 AND (lama_oos_hari <= 0 OR lama_oos_hari IS NULL)");
            }
        } catch (\Throwable $e) {
            // Ignore if not present
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data correction does not require reversal
    }
};
