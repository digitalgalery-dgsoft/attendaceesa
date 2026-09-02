<?php

namespace App\Services;

use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateSyncService
{
    /**
     * Mengemas data template beserta seluruh field & relasinya ke dalam array payload portabel.
     */
    public function exportTemplate(ReportTemplate $template): array
    {
        $template->loadMissing(['fields', 'principals']);

        $fields = $template->fields->map(function (ReportFormField $f) {
            return [
                'field_name' => $f->field_name,
                'field_label' => $f->field_label ?? $f->label ?? $f->field_name,
                'label' => $f->field_label ?? $f->label ?? $f->field_name,
                'field_type' => $f->field_type,
                'placeholder' => $f->placeholder,
                'help_text' => $f->help_text,
                'order_index' => $f->order_index,
                'is_required' => (bool) $f->is_required,
                'is_readonly' => (bool) $f->is_readonly,
                'options' => $f->options,
                'validation_rules' => $f->validation_rules,
            ];
        })->toArray();

        $principalNames = $template->principals->pluck('name')->filter()->unique()->values()->toArray();
        $principalSubdomains = $template->principals->pluck('subdomain')->filter()->unique()->values()->toArray();

        return [
            'template' => [
                'code' => $template->code,
                'title' => $template->title,
                'description' => $template->description,
                'category' => $template->category,
                'require_gps' => (bool) $template->require_gps,
                'require_signature' => (bool) $template->require_signature,
                'min_photos' => (int) ($template->min_photos ?? 0),
                'max_photos' => (int) ($template->max_photos ?? 5),
                'is_active' => (bool) $template->is_active,
                'version' => (int) ($template->version ?? 1),
                'report_days' => $template->report_days ?? [],
                'dashboard_config' => $template->dashboard_config ?? null,
            ],
            'principals' => [
                'names' => $principalNames,
                'subdomains' => $principalSubdomains,
            ],
            'fields' => $fields,
            'source_server' => config('app.url'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Mengimpor data payload ke dalam database lokal (updateOrCreate).
     */
    public function importTemplate(array $payload): ReportTemplate
    {
        return DB::transaction(function () use ($payload) {
            $tplData = $payload['template'] ?? [];
            $code = $tplData['code'] ?? null;

            if (!$code) {
                throw new \InvalidArgumentException('Kode template pelaporan tidak ditemukan dalam payload.');
            }

            // 1. Update or Create ReportTemplate
            $template = ReportTemplate::updateOrCreate(
                ['code' => $code],
                [
                    'title' => $tplData['title'] ?? $code,
                    'description' => $tplData['description'] ?? null,
                    'category' => $tplData['category'] ?? 'general',
                    'require_gps' => (bool) ($tplData['require_gps'] ?? true),
                    'require_signature' => (bool) ($tplData['require_signature'] ?? false),
                    'min_photos' => (int) ($tplData['min_photos'] ?? 0),
                    'max_photos' => (int) ($tplData['max_photos'] ?? 5),
                    'is_active' => (bool) ($tplData['is_active'] ?? true),
                    'version' => (int) ($tplData['version'] ?? 1),
                    'report_days' => $tplData['report_days'] ?? [],
                    'dashboard_config' => $tplData['dashboard_config'] ?? null,
                ]
            );

            // 2. Kaitkan Prinsiple Lokal jika ada yang cocok
            $princNames = $payload['principals']['names'] ?? [];
            $princSubs = $payload['principals']['subdomains'] ?? [];

            if (!empty($princNames) || !empty($princSubs)) {
                $matchedPrincipals = Principal::where(function ($q) use ($princNames, $princSubs) {
                    if (!empty($princNames)) {
                        $q->whereIn('name', $princNames);
                    }
                    if (!empty($princSubs)) {
                        $q->orWhereIn('subdomain', $princSubs);
                    }
                })->pluck('id')->toArray();

                if (!empty($matchedPrincipals)) {
                    $template->principals()->syncWithoutDetaching($matchedPrincipals);
                }
            }

            // 3. Sinkronkan Form Fields
            $fieldsData = $payload['fields'] ?? [];
            $incomingFieldNames = [];

            foreach ($fieldsData as $f) {
                $fieldName = $f['field_name'] ?? null;
                if (!$fieldName) continue;

                $incomingFieldNames[] = $fieldName;

                ReportFormField::updateOrCreate(
                    [
                        'report_template_id' => $template->id,
                        'field_name' => $fieldName,
                    ],
                    [
                        'field_label' => $f['field_label'] ?? $f['label'] ?? $fieldName,
                        'field_type' => $f['field_type'] ?? 'text',
                        'placeholder' => $f['placeholder'] ?? null,
                        'help_text' => $f['help_text'] ?? null,
                        'order_index' => (int) ($f['order_index'] ?? 0),
                        'is_required' => (bool) ($f['is_required'] ?? false),
                        'is_readonly' => (bool) ($f['is_readonly'] ?? false),
                        'options' => $f['options'] ?? null,
                        'validation_rules' => $f['validation_rules'] ?? null,
                    ]
                );
            }

            // Hapus field lama yang sudah dibuang di template asal (jika ada)
            if (!empty($incomingFieldNames)) {
                ReportFormField::where('report_template_id', $template->id)
                    ->whereNotIn('field_name', $incomingFieldNames)
                    ->delete();
            }

            return $template;
        });
    }

    /**
     * Mengirimkan template ini ke seluruh server ESA lainnya (Peer Servers).
     *
     * @return array [ 'server_id' => ['success' => bool, 'message' => string, 'name' => string] ]
     */
    public function syncToPeers(ReportTemplate $template, ?array $selectedServerKeys = null): array
    {
        $payload = $this->exportTemplate($template);
        $servers = config('esa_sync.servers', []);
        $secret = config('esa_sync.secret');
        $currentHost = request()->getHost();

        $results = [];

        foreach ($servers as $key => $info) {
            if ($selectedServerKeys && !in_array($key, $selectedServerKeys)) {
                continue;
            }

            // Jangan kirim ke diri sendiri
            $baseUrl = rtrim($info['base_url'], '/');
            $altUrl = !empty($info['alt_url']) ? rtrim($info['alt_url'], '/') : null;

            if (str_contains($baseUrl, $currentHost) || ($altUrl && str_contains($altUrl, $currentHost))) {
                continue;
            }

            // Coba kirim via base_url, jika gagal coba via alt_url
            $targetUrls = array_filter([$baseUrl, $altUrl]);
            $isSent = false;
            $lastError = '';

            foreach ($targetUrls as $targetUrl) {
                try {
                    $endpoint = "{$targetUrl}/api/v1/sync/report-template";
                    $response = Http::timeout(8)
                        ->withoutVerifying() // Antisipasi sertifikat lokal / self-signed jika ada
                        ->withHeaders([
                            'X-ESA-Sync-Secret' => $secret,
                            'Accept' => 'application/json',
                        ])
                        ->post($endpoint, $payload);

                    if ($response->successful()) {
                        $isSent = true;
                        $results[$key] = [
                            'success' => true,
                            'name' => $info['name'],
                            'message' => 'Berhasil disinkronkan ke ' . $info['name'],
                        ];
                        break;
                    } else {
                        $lastError = 'HTTP ' . $response->status() . ': ' . ($response->json('message') ?? $response->body());
                    }
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                }
            }

            if (!$isSent) {
                Log::warning("Gagal sync template {$template->code} ke {$info['name']}: {$lastError}");
                $results[$key] = [
                    'success' => false,
                    'name' => $info['name'],
                    'message' => 'Gagal: ' . substr($lastError, 0, 150),
                ];
            }
        }

        return $results;
    }
}
