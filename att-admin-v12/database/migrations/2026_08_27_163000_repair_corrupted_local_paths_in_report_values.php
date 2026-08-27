<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ReportSubmissionValue;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $values = ReportSubmissionValue::whereIn('field_type', ['photo', 'camera_photo', 'multi_photo', 'signature'])
            ->orWhereNotNull('media_url')
            ->get();

        foreach ($values as $val) {
            $subId = $val->report_submission_id;
            $fieldId = $val->report_form_field_id;

            $hasLocalPath = false;
            if (is_array($val->value_json)) {
                foreach ($val->value_json as $item) {
                    if (is_string($item) && (str_contains($item, '/data/user/') || str_contains($item, 'data/user/') || str_contains($item, 'cache/wm_'))) {
                        $hasLocalPath = true;
                        break;
                    }
                }
            } elseif (is_string($val->media_url) && (str_contains($val->media_url, '/data/user/') || str_contains($val->media_url, 'data/user/') || str_contains($val->media_url, 'cache/wm_'))) {
                $hasLocalPath = true;
            }

            // Cari file asli yang sudah terupload di storage disk server
            $pattern = "reports/*/report_{$subId}_{$fieldId}_*.jpg";
            $matches = glob(storage_path("app/public/{$pattern}"));
            if (empty($matches)) {
                $pattern2 = "reports/*/report_{$subId}_*.jpg";
                $matches = glob(storage_path("app/public/{$pattern2}"));
            }

            if (!empty($matches)) {
                $diskPaths = [];
                foreach ($matches as $match) {
                    $rel = str_replace(storage_path('app/public/'), '', $match);
                    $rel = str_replace('\\', '/', $rel);
                    $diskPaths[] = ltrim($rel, '/');
                }
                $diskPaths = array_values(array_unique(array_filter($diskPaths)));

                if (!empty($diskPaths)) {
                    $val->update([
                        'value_json' => $diskPaths,
                        'media_url' => $diskPaths[0],
                        'value_text' => implode(', ', $diskPaths),
                    ]);
                }
            } elseif ($hasLocalPath) {
                // Jika file tidak ditemukan di disk server dan path rusak, reset agar tidak broken
                $val->update([
                    'value_json' => null,
                    'media_url' => null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
