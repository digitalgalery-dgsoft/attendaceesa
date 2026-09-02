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
            'amk' => [
                'id' => 'amk',
                'name' => 'Server 1 (PT AMK)',
                'urls' => ['https://amk.esa-solutions.id'],
                'host' => 'amk.esa-solutions.id',
            ],
            'akp' => [
                'id' => 'akp',
                'name' => 'Server 2 (PT AKP)',
                'urls' => ['https://akp.esa-solutions.id'],
                'host' => 'akp.esa-solutions.id',
            ],
            'atk' => [
                'id' => 'atk',
                'name' => 'Server 3 (PT ATK / Gabungan)',
                'urls' => ['https://atk.esa-solutions.id'],
                'host' => 'atk.esa-solutions.id',
            ],
        ];

        $currentServer = env('ESA_CURRENT_SERVER', null);
        $currentAppUrl = strtolower(rtrim(config('app.url', ''), '/'));

        $peers = [];
        foreach ($allServers as $key => $serverInfo) {
            // Lewati server lokal sendiri
            if ($currentServer && $currentServer === $key) {
                continue;
            }
            if ($currentAppUrl && str_contains($currentAppUrl, $key)) {
                continue;
            }
            $peers[$key] = $serverInfo;
        }

        // Fallback jika peers kosong (misal di dev), gunakan semua
        if (empty($peers)) {
            $peers = $allServers;
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
                    $response = Http::timeout(3)->withoutVerifying()->post($endpoint, $payload);

                    if ($response->successful()) {
                        $responseData = $response->json();
                        $token = $responseData['data']['access_token'] ?? null;

                        if ($token) {
                            Cache::put('gateway_relay_token_' . $token, [
                                'target_server' => rtrim($targetUrl, '/'),
                                'target_host' => $serverInfo['host'] ?? '',
                                'server_key' => $serverKey,
                                'employee_id' => $responseData['data']['employee_data']['id'] ?? null,
                            ], now()->addDays(30));
                        }

                        return response()->json($responseData, $response->status());
                    } else {
                        $respJson = $response->json();
                        $msg = $respJson['message'] ?? '';
                        if ($response->status() === 401 || $response->status() === 403 || $response->status() === 422) {
                            if (!empty($msg) && !str_contains(strtolower($msg), 'tidak terdaftar')) {
                                $candidateErrorResponse = response()->json($respJson, $response->status());
                                if (str_contains(strtolower($msg), 'perangkat') || str_contains(strtolower($msg), 'device')) {
                                    return $candidateErrorResponse;
                                }
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

        $cached = Cache::get('gateway_relay_token_' . $token);
        if ($cached && !empty($cached['target_server'])) {
            return $cached;
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
