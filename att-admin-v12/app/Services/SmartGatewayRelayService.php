<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartGatewayRelayService
{
    /**
     * Dapatkan daftar peer server production (selain server saat ini)
     */
    public static function getPeerServers(): array
    {
        $allServers = config('esa_sync.servers', []);
        $currentServer = env('ESA_CURRENT_SERVER', null);
        $currentAppUrl = strtolower(rtrim(config('app.url', ''), '/'));

        $peers = [];
        foreach ($allServers as $key => $server) {
            $baseUrl = rtrim($server['base_url'] ?? '', '/');
            $altUrl = rtrim($server['alt_url'] ?? '', '/');

            // Jangan masukkan diri sendiri jika URL cocok atau jika ID cocok
            if ($currentServer && $currentServer === $key) {
                continue;
            }
            if ($currentAppUrl && (str_contains($currentAppUrl, $key) || $currentAppUrl === strtolower($baseUrl) || $currentAppUrl === strtolower($altUrl))) {
                continue;
            }

            $peers[$key] = [
                'id' => $key,
                'name' => $server['name'] ?? $key,
                'urls' => array_values(array_filter([$baseUrl, $altUrl])),
            ];
        }

        // Jika tidak ada filter yang cocok (misal di development / dev server), daftarkan semua 3 server
        if (empty($peers)) {
            foreach (['amk', 'akp', 'atk'] as $key) {
                if (isset($allServers[$key])) {
                    $peers[$key] = [
                        'id' => $key,
                        'name' => $allServers[$key]['name'] ?? $key,
                        'urls' => array_values(array_filter([$allServers[$key]['base_url'] ?? null, $allServers[$key]['alt_url'] ?? null])),
                    ];
                }
            }
        }

        return $peers;
    }

    /**
     * Coba autentikasi login ke peer servers jika login lokal gagal atau data non-aktif
     */
    public static function attemptRelayLogin(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $peers = self::getPeerServers();
        $payload = [
            'email' => $request->input('email') ?? $request->input('login'),
            'password' => $request->input('password'),
            'device_id' => $request->input('device_id'),
            'device_name' => $request->input('device_name'),
            'fcm_token' => $request->input('fcm_token'),
        ];

        $candidateErrorResponse = null;

        foreach ($peers as $serverKey => $serverInfo) {
            foreach ($serverInfo['urls'] as $targetUrl) {
                if (empty($targetUrl)) {
                    continue;
                }
                try {
                    $endpoint = rtrim($targetUrl, '/') . '/api/login';
                    $response = Http::timeout(5)->withoutVerifying()->post($endpoint, $payload);

                    if ($response->successful()) {
                        $responseData = $response->json();
                        $token = $responseData['data']['access_token'] ?? null;

                        if ($token) {
                            // Simpan token mapping di cache selama 30 hari
                            Cache::put('gateway_relay_token_' . $token, [
                                'target_server' => rtrim($targetUrl, '/'),
                                'server_key' => $serverKey,
                                'employee_id' => $responseData['data']['employee_data']['id'] ?? null,
                            ], now()->addDays(30));
                        }

                        // Kembalikan response sukses dari peer server
                        return response()->json($responseData, $response->status());
                    } else {
                        $respJson = $response->json();
                        $msg = $respJson['message'] ?? '';
                        // Jika pesan error spesifik (password salah atau device locked), simpan sebagai kandidat error
                        if (str_contains(strtolower($msg), 'password') || str_contains(strtolower($msg), 'perangkat') || str_contains(strtolower($msg), 'device')) {
                            $candidateErrorResponse = response()->json($respJson, $response->status());
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("SmartGatewayRelay login failed for {$targetUrl}: " . $e->getMessage());
                }
            }
        }

        return $candidateErrorResponse;
    }

    /**
     * Resolve target server untuk token tertentu
     */
    public static function resolveTargetServer(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $cached = Cache::get('gateway_relay_token_' . $token);
        if ($cached && !empty($cached['target_server'])) {
            return $cached['target_server'];
        }

        return null;
    }

    /**
     * Relay request HTTP secara transparan ke target server (termasuk body, query, files, headers)
     */
    public static function relayRequest(Request $request, string $targetServer): \Symfony\Component\HttpFoundation\Response
    {
        $uri = $request->getRequestUri();
        $targetUrl = rtrim($targetServer, '/') . $uri;
        $method = strtolower($request->method());

        try {
            $headers = [];
            foreach ($request->headers->all() as $headerKey => $headerValues) {
                if (in_array(strtolower($headerKey), ['host', 'content-length'])) {
                    continue;
                }
                $headers[$headerKey] = implode(', ', $headerValues);
            }

            $httpClient = Http::timeout(30)->withoutVerifying()->withHeaders($headers);

            // Jika ada file attachment (misal upload foto absensi / visit / permit / BAP)
            if ($request->hasFile('*') || !empty($request->allFiles())) {
                foreach ($request->allFiles() as $name => $fileOrFiles) {
                    if (is_array($fileOrFiles)) {
                        foreach ($fileOrFiles as $idx => $f) {
                            if ($f && method_exists($f, 'getRealPath')) {
                                $httpClient->attach("{$name}[{$idx}]", file_get_contents($f->getRealPath()), $f->getClientOriginalName());
                            }
                        }
                    } elseif ($fileOrFiles && method_exists($fileOrFiles, 'getRealPath')) {
                        $httpClient->attach($name, file_get_contents($fileOrFiles->getRealPath()), $fileOrFiles->getClientOriginalName());
                    }
                }
                // Kirim form data non-file lainnya
                $response = $httpClient->$method($targetUrl, $request->except(array_keys($request->allFiles())));
            } elseif ($request->isJson()) {
                $response = $httpClient->withBody($request->getContent(), 'application/json')->$method($targetUrl);
            } else {
                $response = $httpClient->$method($targetUrl, $request->all());
            }

            // Copy response headers (kecuali header transfer-encoding untuk menghindari chunking issue)
            $responseHeaders = [];
            foreach ($response->headers() as $k => $v) {
                if (!in_array(strtolower($k), ['transfer-encoding', 'content-length'])) {
                    $responseHeaders[$k] = is_array($v) ? implode(', ', $v) : $v;
                }
            }

            return response($response->body(), $response->status(), $responseHeaders);
        } catch (\Throwable $e) {
            Log::error("SmartGatewayRelay error forwarding to {$targetUrl}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal meneruskan permintaan ke cluster server: ' . $e->getMessage()
            ], 502);
        }
    }
}
