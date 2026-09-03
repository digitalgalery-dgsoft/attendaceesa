<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingSyncController extends Controller
{
    protected SettingSyncService $syncService;

    public function __construct(SettingSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Menerima payload general settings dari server master/peer dan menerapkannya.
     */
    public function syncSettings(Request $request): JsonResponse
    {
        // 1. Verifikasi Sync Secret
        $incomingSecret = $request->header('X-ESA-Sync-Secret');
        $validSecret = config('esa_sync.secret');

        if (empty($incomingSecret) || empty($validSecret) || !hash_equals($validSecret, $incomingSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Header X-ESA-Sync-Secret tidak valid atau tidak cocok.',
            ], 401);
        }

        // 2. Validasi Struktur Payload
        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }
        if (isset($payload['settings']) && is_string($payload['settings'])) {
            $payload['settings'] = json_decode($payload['settings'], true) ?: [];
        }

        if (empty($payload['settings']) || !is_array($payload['settings'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payload tidak valid: array settings wajib ada.',
            ], 422);
        }

        try {
            $setting = $this->syncService->applyIncomingSettings($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'General settings berhasil disinkronkan di server ini.',
                'data' => [
                    'mobile_app_url' => $setting->mobile_app_url,
                    'mobile_app_version' => $setting->mobile_app_version,
                    'is_force_update' => (bool) $setting->is_force_update,
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menerapkan setting: ' . $e->getMessage(),
            ], 500);
        }
    }
}
