<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OdooSyncLog;
use App\Services\OdooSyncService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OdooSyncStreamController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        // Access control
        if (!auth()->check() || (!auth()->user()->isSuperAdmin() && !auth()->user()->can('manage_settings') && !auth()->user()->can('view_odoo_sync'))) {
            abort(403, 'Akses ditolak.');
        }

        @ignore_user_abort(true);
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $companyId = $request->get('company_id');
        $action = $request->get('action', 'all'); // 'test_connection', 'cleanup_duplicates', 'principals', 'employees', 'all', 'all_companies'

        return new StreamedResponse(function () use ($companyId, $action) {
            // Disable output buffers
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', false);

            // Fast flush padding for proxies/webservers (Apache/Nginx/Cloudflare)
            echo ":" . str_repeat(" ", 4096) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();

            $sendEvent = function (string $type, string $message, ?array $meta = null) {
                $payload = [
                    'time' => date('H:i:s'),
                    'type' => $type, // info, success, warning, error, batch, created, updated, resigned, progress, summary, done, ping
                    'message' => $message,
                    'meta' => $meta,
                ];
                echo "data: " . json_encode($payload) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            $sendEvent('info', "🚀 Engine Odoo Sync Inisialisasi...");

            try {
                if ($action === 'test_connection') {
                    $company = Company::find($companyId);
                    if (!$company) {
                        throw new \Exception("Company dengan ID {$companyId} tidak ditemukan.");
                    }
                    $sendEvent('info', "🏢 Menguji koneksi untuk Company: {$company->name}...");
                    $service = OdooSyncService::fromCompany($company);
                    if (!$service) {
                        throw new \Exception("Konfigurasi Odoo (URL, DB, Username, API Key) untuk company ini belum lengkap.");
                    }

                    $service->testConnection($sendEvent);
                    $sendEvent('done', "✅ Pengujian koneksi berhasil.", ['status' => 'success']);
                    return;
                }

                if ($action === 'cleanup_duplicates') {
                    $sendEvent('info', "🧹 Memulai proses pembersihan duplikat NIK...");
                    $cleaned = OdooSyncService::cleanupAllDuplicateEmployees($sendEvent);
                    $sendEvent('done', "✅ Pembersihan duplikat NIK selesai. Total dibersihkan: {$cleaned}", ['status' => 'success', 'cleaned' => $cleaned]);
                    return;
                }

                if ($action === 'all_companies' || $companyId === 'all') {
                    $sendEvent('info', "🏢 Memulai sinkronisasi seluruh perusahaan yang terkonfigurasi...");
                    $batchId = 'SYNC-' . date('Ymd-His') . '-' . \Illuminate\Support\Str::random(6);
                    $results = OdooSyncService::syncAllConfiguredCompanies('manual', $batchId, $sendEvent);
                    $sendEvent('done', "🎉 Seluruh sinkronisasi perusahaan selesai!", ['status' => 'success', 'results' => $results]);
                    return;
                }

                // Specific Company Operations
                $company = Company::find($companyId);
                if (!$company) {
                    throw new \Exception("Pilih Company yang valid terlebih dahulu.");
                }

                $service = OdooSyncService::fromCompany($company);
                if (!$service) {
                    throw new \Exception("Konfigurasi Odoo untuk [{$company->name}] belum lengkap.");
                }

                $batchId = 'SYNC-' . date('Ymd-His') . '-' . \Illuminate\Support\Str::random(6);

                if ($action === 'principals') {
                    $sendEvent('info', "🏢 Memulai Sync Principals untuk: {$company->name}...");
                    $result = $service->syncPrincipals($company->id, null, $sendEvent);
                    $sendEvent('done', "✅ Sync Principals selesai. Baru: {$result['created']}, Update: {$result['updated']}", ['status' => 'success', 'result' => $result]);
                    return;
                }

                if ($action === 'employees') {
                    $sendEvent('info', "👥 Memulai Sync Employees untuk: {$company->name}...");
                    $result = $service->syncEmployees($company->id, null, $sendEvent);

                    // Save log
                    $totalActive = Employee::where('company_id', $company->id)->where('is_active', true)->count();
                    OdooSyncLog::create([
                        'batch_id' => $batchId,
                        'company_id' => $company->id,
                        'sync_type' => 'employee',
                        'trigger_type' => 'manual',
                        'status' => empty($result['errors']) ? 'success' : 'partial',
                        'new_count' => $result['created'],
                        'update_count' => $result['updated'],
                        'resign_count' => $result['resigned'] ?? 0,
                        'total_employee_count' => $totalActive,
                        'details' => $result,
                    ]);

                    $sendEvent('done', "✅ Sync Employees selesai. Baru: {$result['created']}, Update: {$result['updated']}, Resign: " . ($result['resigned'] ?? 0), ['status' => 'success', 'result' => $result]);
                    return;
                }

                if ($action === 'all') {
                    $sendEvent('info', "⚡ Memulai Sync Lengkap (Principal + Employee) untuk: {$company->name}...");
                    
                    // 1. Principal
                    $sendEvent('info', "--- TAHAP 1: SINKRONISASI PRINCIPALS ---");
                    $pResult = $service->syncPrincipals($company->id, null, $sendEvent);
                    
                    // 2. Employee
                    $sendEvent('info', "--- TAHAP 2: SINKRONISASI EMPLOYEES ---");
                    $eResult = $service->syncEmployees($company->id, null, $sendEvent);

                    $allErrors = array_merge($pResult['errors'] ?? [], $eResult['errors'] ?? []);
                    $totalActive = Employee::where('company_id', $company->id)->where('is_active', true)->count();

                    OdooSyncLog::create([
                        'batch_id' => $batchId,
                        'company_id' => $company->id,
                        'sync_type' => 'all',
                        'trigger_type' => 'manual',
                        'status' => empty($allErrors) ? 'success' : 'partial',
                        'new_count' => $eResult['created'],
                        'update_count' => $eResult['updated'],
                        'resign_count' => $eResult['resigned'] ?? 0,
                        'total_employee_count' => $totalActive,
                        'details' => [
                            'principals' => $pResult,
                            'employees' => $eResult,
                        ],
                    ]);

                    $sendEvent('done', "🎉 Sync All Selesai untuk {$company->name}!", [
                        'status' => 'success',
                        'summary' => [
                            'principal_created' => $pResult['created'],
                            'principal_updated' => $pResult['updated'],
                            'employee_created' => $eResult['created'],
                            'employee_updated' => $eResult['updated'],
                            'employee_resigned' => $eResult['resigned'] ?? 0,
                            'total_employees' => $totalActive,
                        ]
                    ]);
                    return;
                }

                throw new \Exception("Aksi '{$action}' tidak dikenali.");

            } catch (\Throwable $e) {
                $sendEvent('error', "💥 ERROR: " . $e->getMessage());
                $sendEvent('done', "❌ Proses terhenti karena error.", ['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
