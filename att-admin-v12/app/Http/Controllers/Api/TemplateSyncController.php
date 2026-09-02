<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TemplateSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateSyncController extends Controller
{
    protected TemplateSyncService $syncService;

    public function __construct(TemplateSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Menerima payload template laporan dari server ESA lain dan mengimpornya.
     */
    public function syncReportTemplate(Request $request): JsonResponse
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
        $payload = $request->all();
        if (empty($payload['template']['code'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payload tidak valid: template.code wajib ada.',
            ], 422);
        }

        try {
            $template = $this->syncService->importTemplate($payload);

            return response()->json([
                'status' => 'success',
                'message' => "Template '{$template->title}' ({$template->code}) berhasil disinkronkan di server ini.",
                'data' => [
                    'id' => $template->id,
                    'code' => $template->code,
                    'title' => $template->title,
                    'fields_count' => $template->fields()->count(),
                    'updated_at' => $template->updated_at->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengimpor template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint status sync server ini.
     */
    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'server' => config('app.url'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
