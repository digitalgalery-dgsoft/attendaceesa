<?php

define('LARAVEL_START', microtime(true));
@ini_set('memory_limit', '1024M');
@set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Company;
use App\Models\Principal;
use Illuminate\Support\Facades\DB;

$token = $_GET['token'] ?? '';
if ($token !== 'dgsoft_rahasia_123') {
    http_response_code(403);
    die("<h2 style='color:red; font-family:sans-serif;'>403 Forbidden: Token tidak valid atau tidak disertakan.</h2>");
}

$action = $_GET['action'] ?? 'inspect';
$querySearch = $_GET['q'] ?? '';
$message = '';
$msgType = 'info';

// Hapus Seluruh Data Employee
if ($action === 'wipe' && isset($_POST['confirm_wipe'])) {
    try {
        $driver = DB::getDriverName();
        $totalBefore = Employee::withTrashed()->count();

        if ($driver === 'pgsql') {
            DB::statement("TRUNCATE TABLE employees CASCADE;");
        } else {
            DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
            DB::statement("TRUNCATE TABLE employees;");
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
        }

        $message = "✅ BERHASIL MENGHAPUS SELURUH DATA EMPLOYEE! (Total {$totalBefore} data karyawan telah dibersihkan secara permanen). Database siap untuk fresh sync.";
        $msgType = 'success';
        $action = 'inspect';
    } catch (\Throwable $e) {
        $message = "❌ GAGAL MENGHAPUS DATA: " . $e->getMessage();
        $msgType = 'error';
    }
}

// Statistik Data
$totalAll = Employee::withTrashed()->count();
$totalActive = Employee::where('is_active', true)->count();
$totalInactive = Employee::where('is_active', false)->count();
$totalTrashed = Employee::onlyTrashed()->count();

// Pencarian Spesifik
$searchResults = collect();
if (!empty($querySearch)) {
    $searchResults = Employee::withTrashed()
        ->where(function($q) use ($querySearch) {
            $q->where('full_name', 'ilike', "%{$querySearch}%")
              ->orWhere('employee_no', 'ilike', "%{$querySearch}%");
        })
        ->with(['company', 'principal', 'branch', 'position', 'department'])
        ->get();
}

// Sample Data Terkini
$sampleEmployees = Employee::withTrashed()
    ->with(['company', 'principal', 'branch', 'position'])
    ->orderByDesc('id')
    ->limit(30)
    ->get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage & Wipe Employee Data (Temporary Utility)</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 24px;
            margin: 0;
            line-height: 1.5;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3);
        }
        h1, h2, h3 { margin-top: 0; color: #fff; }
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #0f172a;
            border: 1px solid #334155;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-num {
            font-size: 28px;
            font-weight: 800;
            font-family: monospace;
            margin-top: 4px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-danger {
            background: #dc2626;
            color: #fff;
        }
        .btn-danger:hover { background: #b91c1c; }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover { background: #1d4ed8; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 12px;
        }
        th, td {
            padding: 8px 12px;
            border-bottom: 1px solid #334155;
            text-align: left;
        }
        th {
            background: #0f172a;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 11px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-green { background: #166534; color: #4ade80; }
        .badge-red { background: #991b1b; color: #f87171; }
        .badge-gray { background: #374151; color: #9ca3af; }
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1>🛠️ Employee Data Manager & Reset Tool</h1>
            <div style="color: #94a3b8; font-size: 13px;">Gunakan tool ini untuk memeriksa status database dan mereset data karyawan sebelum fresh sync.</div>
        </div>
        <div>
            <a href="?token=dgsoft_rahasia_123" class="btn btn-primary">🔄 Refresh Status</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    {{-- Statistik --}}
    <div class="grid-stats">
        <div class="stat-box">
            <div style="color: #94a3b8; font-size: 12px;">TOTAL EMPLOYEE</div>
            <div class="stat-num" style="color: #38bdf8;"><?= number_format($totalAll) ?></div>
        </div>
        <div class="stat-box">
            <div style="color: #94a3b8; font-size: 12px;">STATUS AKTIF</div>
            <div class="stat-num" style="color: #4ade80;"><?= number_format($totalActive) ?></div>
        </div>
        <div class="stat-box">
            <div style="color: #94a3b8; font-size: 12px;">STATUS RESIGN / NON-AKTIF</div>
            <div class="stat-num" style="color: #fbbf24;"><?= number_format($totalInactive) ?></div>
        </div>
        <div class="stat-box">
            <div style="color: #94a3b8; font-size: 12px;">TERHAPUS (SOFT DELETED)</div>
            <div class="stat-num" style="color: #f87171;"><?= number_format($totalTrashed) ?></div>
        </div>
    </div>

    {{-- Form Pencarian Data Spesifik --}}
    <div class="card">
        <h3>🔍 Cari Karyawan di Database (Cek Apakah Sudah Tersimpan)</h3>
        <form method="GET" action="" style="display: flex; gap: 10px;">
            <input type="hidden" name="token" value="dgsoft_rahasia_123">
            <input type="text" name="q" value="<?= htmlspecialchars($querySearch) ?>" placeholder="Masukkan Nama atau NIK (contoh: HILDA MARSYA)" style="flex: 1; padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #fff; font-size: 14px;">
            <button type="submit" class="btn btn-primary">Cari Data</button>
            <?php if (!empty($querySearch)): ?>
                <a href="?token=dgsoft_rahasia_123" class="btn" style="background: #475569; color: #fff;">Reset Pencarian</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($querySearch)): ?>
            <div style="margin-top: 16px;">
                <h4>Hasil Pencarian untuk: "<?= htmlspecialchars($querySearch) ?>" (Ditemukan: <?= $searchResults->count() ?> data)</h4>
                <?php if ($searchResults->isNotEmpty()): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NIK / No KTP</th>
                                <th>Nama Karyawan</th>
                                <th>Company</th>
                                <th>Prinsiple</th>
                                <th>Area / Branch</th>
                                <th>Jabatan</th>
                                <th>Status Aktif</th>
                                <th>Soft Deleted?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $emp): ?>
                                <tr>
                                    <td><?= $emp->id ?></td>
                                    <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($emp->employee_no) ?></td>
                                    <td><strong><?= htmlspecialchars($emp->full_name) ?></strong></td>
                                    <td><?= htmlspecialchars($emp->company->name ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp->principal->name ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp->branch->name ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp->position->name ?? '-') ?></td>
                                    <td>
                                        <?php if ($emp->is_active): ?>
                                            <span class="badge badge-green">AKTIF</span>
                                        <?php else: ?>
                                            <span class="badge badge-red">RESIGN (Non-Aktif)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($emp->deleted_at): ?>
                                            <span class="badge badge-red">TRASHED (<?= $emp->deleted_at ?>)</span>
                                        <?php else: ?>
                                            <span class="badge badge-green">NORMAL</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="color: #94a3b8; padding: 10px; background: #0f172a; border-radius: 6px;">Tidak ada record karyawan dengan nama/NIK tersebut di database.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    {{-- Tombol Hapus Seluruh Data Employee --}}
    <div class="card" style="border-color: #ef4444;">
        <h3 style="color: #f87171;">⚠️ Hapus Seluruh Data Karyawan (Wipe All Employees)</h3>
        <p style="font-size: 13px; color: #cbd5e1;">
            Tindakan ini akan <strong>mengosongkan seluruh tabel <code>employees</code></strong> di database (baik aktif, resign, maupun soft-deleted) dengan aman.
            Data master lainnya seperti <strong>Company, User Login Super Admin, Prinsiple, Area, Jabatan, dan Departemen tetap aman dan tidak terhapus</strong>.
        </p>

        <form method="POST" action="?token=dgsoft_rahasia_123&action=wipe" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin MENGHAPUS SELURUH DATA KARYAWAN (Total: <?= $totalAll ?> record)? Data akan dikosongkan untuk fresh sync.');">
            <input type="hidden" name="confirm_wipe" value="1">
            <button type="submit" class="btn btn-danger" style="font-size: 15px; padding: 12px 24px;">
                🗑️ Hapus Seluruh <?= number_format($totalAll) ?> Data Employee Sekarang
            </button>
        </form>
    </div>

    {{-- 30 Data Terkini --}}
    <div class="card">
        <h3>📋 30 Data Karyawan Terakhir di Database</h3>
        <?php if ($sampleEmployees->isNotEmpty()): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Company</th>
                        <th>Prinsiple</th>
                        <th>Area</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sampleEmployees as $emp): ?>
                        <tr>
                            <td><?= $emp->id ?></td>
                            <td style="font-family: monospace;"><?= htmlspecialchars($emp->employee_no) ?></td>
                            <td><?= htmlspecialchars($emp->full_name) ?></td>
                            <td><?= htmlspecialchars($emp->company->name ?? '-') ?></td>
                            <td><?= htmlspecialchars($emp->principal->name ?? '-') ?></td>
                            <td><?= htmlspecialchars($emp->branch->name ?? '-') ?></td>
                            <td><?= htmlspecialchars($emp->position->name ?? '-') ?></td>
                            <td>
                                <?php if ($emp->is_active): ?>
                                    <span class="badge badge-green">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-red">Resign</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="color: #94a3b8;">Tabel employees saat ini kosong (0 data).</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
