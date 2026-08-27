<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $values = DB::table('report_submission_values')
            ->whereNotNull('media_url')
            ->orWhereNotNull('value_json')
            ->orWhereIn('field_type', ['photo', 'camera_photo', 'multi_photo', 'signature'])
            ->get();

        foreach ($values as $v) {
            $updated = [];

            // Clean media_url
            if (!empty($v->media_url)) {
                $m = trim($v->media_url);
                if (str_contains($m, '/storage/')) {
                    $parts = explode('/storage/', $m);
                    $m = ltrim(end($parts), '/');
                } elseif (str_starts_with($m, 'storage/')) {
                    $m = ltrim(substr($m, 8), '/');
                }
                $updated['media_url'] = $m;
            }

            // Clean value_json
            if (!empty($v->value_json)) {
                $raw = is_string($v->value_json) ? json_decode($v->value_json, true) : (array)$v->value_json;
                if (is_array($raw)) {
                    $cleanedArr = [];
                    foreach ($raw as $item) {
                        if (empty($item) || !is_string($item)) continue;
                        $cleanedItem = trim($item);
                        if (str_contains($cleanedItem, '/storage/')) {
                            $parts = explode('/storage/', $cleanedItem);
                            $cleanedItem = ltrim(end($parts), '/');
                        } elseif (str_starts_with($cleanedItem, 'storage/')) {
                            $cleanedItem = ltrim(substr($cleanedItem, 8), '/');
                        }
                        $cleanedArr[] = $cleanedItem;
                    }
                    if (!empty($cleanedArr)) {
                        $updated['value_json'] = json_encode(array_values(array_unique($cleanedArr)));
                        if (empty($updated['media_url'])) {
                            $updated['media_url'] = $cleanedArr[0];
                        }
                    }
                }
            }

            if (!empty($updated)) {
                DB::table('report_submission_values')
                    ->where('id', $v->id)
                    ->update($updated);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
