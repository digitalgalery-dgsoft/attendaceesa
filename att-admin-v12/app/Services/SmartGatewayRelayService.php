<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartGatewayRelayService
{
    /**
     * Dapatkan daftar peer server cluster
     */
    public static function getPeerServers(): array
    {
        $allServers = [
            'atk' => [
                'id' => 'atk',
                'name' => 'Server 3 (PT ATK / Gabungan)',
                'urls' => ['https://atk.esa-solutions.id'],
                'host' => 'atk.esa-solutions.id',
                'ips'  => ['38.103.170.224'],
            ],
            'akp' => [
                'id' => 'akp',
                'name' => 'Server 2 (PT AKP)',
                'urls' => ['https://akp.esa-solutions.id'],
                'host' => 'akp.esa-solutions.id',
                'ips'  => ['38.103.170.223'],
            ],
            'amk' => [
                'id' => 'amk',
                'name' => 'Server 1 (PT AMK)',
                'urls' => ['https://amk.esa-solutions.id'],
                'host' => 'amk.esa-solutions.id',
                'ips'  => ['38.103.170.235', '38.103.170.222'],
            ],
        ];

        // Deteksi identitas node server saat ini
        $currentNode = env('ESA_CURRENT_SERVER', null);
        if (!$currentNode && class_exists(\App\Services\ServerTelemetryService::class)) {
            $currentNode = \App\Services\ServerTelemetryService::getCurrentNodeId();
        }

        $currentAppUrl = strtolower(rtrim(config('app.url', ''), '/'));
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        $httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');

        $peers = [];
        foreach ($allServers as $key => $serverInfo) {
            // Lewati server lokal sendiri
            if ($currentNode && $currentNode === $key) {
                continue;
            }
            if ($currentAppUrl && str_contains($currentAppUrl, $key)) {
                continue;
            }
            if ($httpHost && str_contains($httpHost, $key)) {
                continue;
            }
            if ($serverAddr && in_array($serverAddr, $serverInfo['ips'] ?? [])) {
                continue;
            }
            $peers[$key] = $serverInfo;
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
            'email' => $request->input('email') ?? $request->input('login') ?? $request->input('username') ?? $request->input('employee_no'),
            'password' => $request->input('password'),
            'device_id' => $request->input('device_id'),
            'device_name' => $request->input('device_name'),
            'fcm_token' => $request->input('fcm_token'),
        ];

        $candidateErrorResponse = null;

        foreach ($peers as $serverKey => $serverInfo) {
            foreach ($serverInfo['urls'] as $targetUrl) {
                if (empty($targetUrl)) continue;
                try {
                    $endpoint = rtrim($targetUrl, '/') . '/api/login';
                    $response = Http::timeout(3)->withoutVerifying()->withHeaders([
                        'X-ESA-Gateway-Relay' => '1',
                    ])->post($endpoint, $payload);

                    if ($response->successful()) {
                        $responseData = $response->json();
                        $token = $responseData['data']['access_token'] ?? null;

                        if ($token) {
                            Cache::put('gateway_relay_token_' . $token, [
                                'target_server' => rtrim($targetUrl, '/'),
                                'target_host' => $serverInfo['host'] ?? '',
                                'server_key' => $serverKey,
                                'employee_id' => $responseData['data']['employee_data']['id'] ?? null,
                            ], now()->addDays(180));
                        }

                        return response()->json($responseData, $response->status());
                    } else {
                        $respJson = $response->json();
                        $msg = $respJson['message'] ?? '';
                        if ($response->status() === 401 || $response->status() === 403 || $response->status() === 422) {
                            if (!empty($msg) && !str_contains(strtolower($msg), 'tidak terdaftar')) {
                                // Peer server mengenali NIK user! Kembalikan respon segera tanpa perlu mencoba server lain
                                return response()->json($respJson, $response->status());
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("SmartGatewayRelay error for {$targetUrl}: " . $e->getMessage());
                }
            }
        }

        return $candidateErrorResponse;
    }

    /**
     * Resolve target server untuk token tertentu
     */
    public static function resolveTargetServer(?string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        // Cek jika token sudah pernah dipastikan TIDAK VALID / non-existent (negative caching)
        // untuk mencegah request berulang membanjiri jaringan peer servers
        if (Cache::has('gateway_relay_token_invalid_' . $token)) {
            return null;
        }

        $cached = Cache::get('gateway_relay_token_' . $token);
        if ($cached && !empty($cached['target_server'])) {
            return $cached;
        }

        // Fallback: Jika cache hilang (misal akibat artisan cache:clear atau restart server),
        // cek apakah token ada di database lokal personal_access_tokens.
        // Jika TIDAK ADA di database lokal, berarti token ini milik peer server cluster (AKP / ATK).
        try {
            $isLocalToken = false;
            if (\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                [$tokenId] = explode('|', $token, 2);
                if (is_numeric($tokenId)) {
                    $isLocalToken = \Illuminate\Support\Facades\DB::table('personal_access_tokens')
                        ->where('id', (int) $tokenId)
                        ->exists();
                }
            }

            if (!$isLocalToken) {
                $peers = self::getPeerServers();
                foreach ($peers as $serverKey => $serverInfo) {
                    foreach ($serverInfo['urls'] as $targetUrl) {
                        if (empty($targetUrl)) continue;
                        $endpoint = rtrim($targetUrl, '/') . '/api/me';
                        try {
                            $res = Http::timeout(2)->withoutVerifying()->withHeaders([
                                'Authorization'       => 'Bearer ' . $token,
                                'Accept'              => 'application/json',
                                'X-ESA-Gateway-Relay' => '1',
                            ])->get($endpoint);

                            if ($res->successful()) {
                                $targetInfo = [
                                    'target_server' => rtrim($targetUrl, '/'),
                                    'target_host'   => $serverInfo['host'] ?? '',
                                    'server_key'    => $serverKey,
                                    'employee_id'   => $res->json('data.employee_data.id'),
                                ];
                                Cache::put('gateway_relay_token_' . $token, $targetInfo, now()->addDays(180));
                                return $targetInfo;
                            }
                        } catch (\Throwable $err) {
                            Log::warning("SmartGatewayRelay resolveTargetServer probe failed for {$endpoint}: " . $err->getMessage());
                        }
                    }
                }

                // Jika sudah dicek ke seluruh peer servers tapi tidak ada yang mengenali token ini,
                // simpan ke Negative Cache selama 30 menit agar tidak membebani worker cluster!
                Cache::put('gateway_relay_token_invalid_' . $token, true, now()->addMinutes(30));
            }
        } catch (\Throwable $e) {
            Log::warning("SmartGatewayRelay fallback error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Relay request HTTP secara transparan ke target server cluster
     */
    public static function relayRequest(Request $request, array $targetInfo): \Symfony\Component\HttpFoundation\Response
    {
        $targetServer = $targetInfo['target_server'] ?? '';
        $targetHost = $targetInfo['target_host'] ?? '';
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

            if (!empty($targetHost)) {
                $headers['Host'] = $targetHost;
            }

            $hasFiles = $request->hasFile('*') || !empty($request->allFiles());
            // WAJIB: Hapus Content-Type jika BUKAN JSON (misal multipart/form-data atau urlencoded)
            // agar Guzzle men-generate boundary multipart sendiri jika ada file attachment,
            // atau menggunakan application/x-www-form-urlencoded murni jika tanpa file.
            if (!$request->isJson()) {
                foreach (array_keys($headers) as $k) {
                    if (strtolower($k) === 'content-type') {
                        unset($headers[$k]);
                    }
                }
            }

            $httpClient = Http::timeout(30)->withoutVerifying()->withHeaders($headers);

            // Jika ada file attachment (misal upload foto absensi / visit / permit / BAP / profile)
            if ($hasFiles) {
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
                $response = $httpClient->$method($targetUrl, $request->except(array_keys($request->allFiles())));
            } elseif ($request->isJson()) {
                $response = $httpClient->withBody($request->getContent(), 'application/json')->$method($targetUrl);
            } else {
                $response = $httpClient->$method($targetUrl, $request->all());
            }

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
