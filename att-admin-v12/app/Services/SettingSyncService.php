<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SettingSyncService
{
    /**
     * Daftar atribut setting yang disinkronkan antar server cluster.
     */
    protected array $syncableFields = [
        'app_name',
        'mobile_app_url',
        'mobile_app_version',
        'is_force_update',
        'company_name',
        'contact_email',
        'contact_phone',
        'office_address',
        'service_hours',
        'privacy_policy_url',
        'tracking_distance_meters',
        'tracking_interval_minutes',
        'dark_mode_enabled',
        'dark_mode_theme',
    ];

    /**
     * Sinkronkan perubahan setting ke seluruh server ESA lainnya (Peer Servers).
     *
     * @param array $data Data setting baru
     * @return array Hasil sinkronisasi per server
     */
    public function syncToPeers(array $data): array
    {
        $payload = [
            'settings' => array_intersect_key($data, array_flip($this->syncableFields)),
            'source_server' => config('app.url'),
            'timestamp' => now()->toIso8601String(),
        ];

        $servers = config('esa_sync.servers', []);
        $secret = config('esa_sync.secret');
        $currentHost = request()->getHost();

        $results = [];

        foreach ($servers as $key => $info) {
            $baseUrl = rtrim($info['base_url'], '/');
            $altUrl = !empty($info['alt_url']) ? rtrim($info['alt_url'], '/') : null;

            // Jangan kirim ke diri sendiri
            if (str_contains($baseUrl, $currentHost) || ($altUrl && str_contains($altUrl, $currentHost))) {
                continue;
            }

            $targetUrls = array_filter([$baseUrl, $altUrl]);
            $isSent = false;
            $lastError = '';

            foreach ($targetUrls as $targetUrl) {
                try {
                    $endpoint = "{$targetUrl}/api/v1/sync/settings";
                    $response = Http::timeout(6)
                        ->withoutVerifying()
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
                Log::warning("Gagal sinkronisasi general setting ke {$info['name']}: {$lastError}");
                $results[$key] = [
                    'success' => false,
                    'name' => $info['name'],
                    'message' => 'Gagal: ' . substr($lastError, 0, 150),
                ];
            }
        }

        return $results;
    }

    /**
     * Menerapkan pengaturan yang dikirim dari server master/peer ke database lokal.
     */
    public function applyIncomingSettings(array $payload): Setting
    {
        $incoming = $payload['settings'] ?? [];
        if (empty($incoming) || !is_array($incoming)) {
            throw new \InvalidArgumentException('Payload settings kosong atau tidak valid.');
        }

        $filtered = array_intersect_key($incoming, array_flip($this->syncableFields));

        $setting = Setting::first();
        if ($setting) {
            $setting->update($filtered);
        } else {
            $setting = Setting::create($filtered);
        }

        // Bersihkan cache pengaturan publik dan landing
        Cache::forget('public_app_system_setting_array_v2');
        Cache::forget('global_landing_stats_active_v3');

        return $setting;
    }
}
