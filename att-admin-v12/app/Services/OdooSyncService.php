<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Principal;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class OdooSyncService
{
    private string $url;
    private string $db;
    private string $username;
    private string $apiKey;
    private ?int $uid = null;

    public function __construct(string $url, string $db, string $username, string $apiKey)
    {
        $this->url      = rtrim(trim($url), '/');
        $this->db       = trim($db);
        $this->username = trim($username);
        $this->apiKey   = trim($apiKey);
    }

    /**
     * List all database names available on the Odoo server.
     */
    public static function listDatabases(string $url): array
    {
        $cleanUrl = rtrim(trim($url), '/');
        $service = new self($cleanUrl, '', '', '');
        try {
            $result = $service->xmlRpcCall('/xmlrpc/2/db', 'list', []);
            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Build an Odoo XML-RPC service instance from a specific company.
     */
    public static function fromCompany(Company $company): ?self
    {
        if (!$company->odoo_url || !$company->odoo_db || !$company->odoo_username || !$company->odoo_api_key) {
            return null;
        }
        return new self($company->odoo_url, $company->odoo_db, $company->odoo_username, $company->odoo_api_key);
    }

    /**
     * Authenticate with Odoo and return uid.
     */
    public function authenticate(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        try {
            $response = $this->xmlRpcCall('/xmlrpc/2/common', 'authenticate', [
                $this->db,
                $this->username,
                $this->apiKey,
                [],
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'KeyError') || str_contains($msg, 'registry') || str_contains($msg, 'Fault [1]')) {
                $availableDbs = self::listDatabases($this->url);
                $dbListText = !empty($availableDbs) 
                    ? (" Database yang terdeteksi aktif di server ini: [" . implode(', ', $availableDbs) . "].") 
                    : "";
                throw new \Exception("Database '{$this->db}' tidak ditemukan di instance server Odoo ({$this->url})." . $dbListText . " Pastikan nama database sesuai atau cek apakah perusahaan ini menggunakan database bersama (Multi-Company).");
            }
            throw $e;
        }

        if (!$response || !is_int($response)) {
            throw new \Exception("Autentikasi Odoo gagal. Pastikan Username/Email dan API Key benar.");
        }

        $this->uid = $response;
        return $this->uid;
    }

    /**
     * Test connection and return Odoo server version.
     */
    public function testConnection(?callable $progressCallback = null): array
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        $log('info', "📡 Menguji koneksi ke server Odoo ({$this->url})...");
        $version = $this->xmlRpcCall('/xmlrpc/2/common', 'version', []);
        $serverVersion = $version['server_version'] ?? 'unknown';
        $log('info', "ℹ️ Odoo Server terdeteksi versi: {$serverVersion}");

        $log('info', "🔑 Mengautentikasi database '{$this->db}' dengan user '{$this->username}'...");
        $uid = $this->authenticate();
        $log('success', "🎉 Autentikasi BERHASIL! Terhubung sebagai UID: {$uid}");

        return [
            'success'       => true,
            'uid'           => $uid,
            'server_version'=> $serverVersion,
        ];
    }

    /**
     * Sync Principals (res.partner with is_principal = True) from Odoo.
     * @param int $companyId - Local company ID to associate principals with
     * @param int|null $odooCompanyId - Odoo company_id to filter by (optional)
     * @param callable|null $progressCallback - Optional callback for live progress streaming
     */
    public function syncPrincipals(int $companyId, ?int $odooCompanyId = null, ?callable $progressCallback = null): array
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        $log('info', "🔑 Autentikasi ke Odoo untuk sync Principals...");
        $uid = $this->authenticate();

        $domain = [['is_principal', '=', true]];
        if ($odooCompanyId) {
            $domain[] = ['company_id', '=', $odooCompanyId];
        }

        try {
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE principals DROP CONSTRAINT IF EXISTS principals_odoo_id_unique;");
                \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS principals_odoo_id_unique;");
            }
        } catch (\Throwable $e) {}

        $created  = 0;
        $updated  = 0;
        $errors   = [];
        $offset   = 0;
        $limit    = 500;
        $batchNum = 0;

        do {
            $batchNum++;
            $log('batch', "📦 Mengambil Principal Batch #{$batchNum} (Offset: {$offset}, Limit: {$limit})...");

            $records = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
                $this->db, $uid, $this->apiKey,
                'res.partner', 'search_read',
                [$domain],
                [
                    'fields' => ['id', 'name', 'code_principal', 'ref', 'active'],
                    'order'  => 'write_date desc, id desc',
                    'limit'  => $limit,
                    'offset' => $offset,
                ],
            ]);

            $recCount = count($records);
            $log('info', "📥 Diterima {$recCount} data Principal dari Odoo.");

            foreach ($records as $rec) {
                try {
                    $code = $rec['code_principal'] ?? $rec['ref'] ?? null;
                    $finalCode = $code ?: ('OD-' . $rec['id']);

                    // 1. Try to find by odoo_id first
                    $principal = Principal::where('odoo_id', $rec['id'])->first();

                    // 2. If not found, try to find by code
                    if (!$principal) {
                        $principal = Principal::where('code', $finalCode)->first();
                    }

                    if ($principal) {
                        // Check code conflict before update
                        $conflict = Principal::where('code', $finalCode)->where('id', '!=', $principal->id)->first();
                        if ($conflict) {
                            $finalCode = $finalCode . '-OD' . $rec['id'];
                        }

                        $principal->update([
                            'odoo_id' => $rec['id'],
                            'name' => $rec['name'],
                            'code' => $finalCode,
                            'company_id' => $companyId,
                        ]);
                        $updated++;
                        $log('updated', "🔄 [UPDATE] Principal: {$rec['name']} (Kode: {$finalCode})");
                    } else {
                        // Check code conflict before create
                        $conflict = Principal::where('code', $finalCode)->first();
                        if ($conflict) {
                            $finalCode = $finalCode . '-OD' . $rec['id'];
                        }

                        Principal::create([
                            'odoo_id' => $rec['id'],
                            'name' => $rec['name'],
                            'code' => $finalCode,
                            'company_id' => $companyId,
                        ]);
                        $created++;
                        $log('created', "➕ [BARU] Principal: {$rec['name']} (Kode: {$finalCode})");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Principal [{$rec['name']}]: " . $e->getMessage();
                    $log('error', "⚠️ Error Principal [{$rec['name']}]: " . $e->getMessage());
                    Log::error('Odoo Sync Principal Error', ['record' => $rec, 'error' => $e->getMessage()]);
                }
            }
            $offset += $limit;
        } while (count($records) == $limit);

        $log('summary', "🏢 Selesai Sync Principals! Total Baru: {$created} | Diperbarui: {$updated}" . (count($errors) > 0 ? " | Error: " . count($errors) : ""), [
            'created' => $created,
            'updated' => $updated,
            'errors' => count($errors),
        ]);

        return compact('created', 'updated', 'errors');
    }

    /**
     * Drop legacy PostgreSQL constraints that prevent multi-company sync.
     */
    public static function dropLegacyConstraints(): void
    {
        try {
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_odoo_id_unique;");
                \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS employees_odoo_id_unique;");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE principals DROP CONSTRAINT IF EXISTS principals_odoo_id_unique;");
                \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS principals_odoo_id_unique;");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_company_id_employee_no_unique;");
                \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS employees_company_id_employee_no_unique;");
            }
        } catch (\Throwable $e) {
            // Ignore if already dropped
        }
    }

    /**
     * Query total number of employees in Odoo.
     */
    public function countOdooEmployees(int $companyId, ?int $odooCompanyId = null): int
    {
        self::dropLegacyConstraints();
        $uid = $this->authenticate();
        $domain = [];
        if ($odooCompanyId) {
            $domain[] = ['company_id', '=', $odooCompanyId];
        }

        try {
            $countRes = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
                $this->db, $uid, $this->apiKey,
                'hr.employee', 'search_count',
                [$domain],
                ['context' => ['active_test' => false]],
            ]);
            if (is_int($countRes)) {
                return $countRes;
            }
        } catch (\Throwable $e) {
            Log::warning("OdooSync count error: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Process a single batch of employees from Odoo.
     */
    public function syncEmployeesBatch(int $companyId, int $offset, int $limit = 250, ?int $odooCompanyId = null, ?callable $progressCallback = null, int $batchNum = 1, int $totalOdooEmployees = 0): array
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        \Illuminate\Support\Facades\DB::disableQueryLog();
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $uid = $this->authenticate();
        $domain = [];
        if ($odooCompanyId) {
            $domain[] = ['company_id', '=', $odooCompanyId];
        }

        $localCompany = Company::find($companyId);
        $created  = 0;
        $updated  = 0;
        $resigned = 0;
        $errors   = [];
        $newEmployees = [];
        $updatedEmployees = [];
        $resignedEmployees = [];

        $batchLabel = $totalOdooEmployees > 0 ? "Batch #{$batchNum}/" . ceil($totalOdooEmployees / $limit) : "Batch #{$batchNum}";
        $log('batch', "📦 Mengambil Data Karyawan {$batchLabel} (Offset: {$offset}, Limit: {$limit})...", [
            'batch' => $batchNum,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $totalOdooEmployees,
        ]);

        $records = [];
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $records = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
                    $this->db, $uid, $this->apiKey,
                    'hr.employee', 'search_read',
                    [$domain],
                    [
                        'fields' => [
                            'id', 'name', 'registration_number', 'identification_id',
                            'mobile_phone', 'work_email', 'private_email', 'gender', 'birthday',
                            'department_id', 'job_id', 'principle_id', 'first_contract_date',
                            'area_id', 'company_id', 'active', 'departure_date',
                            'write_date', 'create_date',
                        ],
                        'context' => ['active_test' => false],
                        'order'   => 'write_date desc, id desc',
                        'limit'   => $limit,
                        'offset'  => $offset,
                    ],
                ]);
                break;
            } catch (\Throwable $e) {
                if ($attempts >= $maxAttempts) {
                    $log('error', "❌ Gagal mengambil {$batchLabel} setelah {$maxAttempts} kali percobaan: " . $e->getMessage());
                    throw $e;
                }
                $log('warning', "⚠️ Percobaan {$attempts} gagal mengambil {$batchLabel} ({$e->getMessage()}). Mencoba ulang dalam 2 detik...");
                sleep(2);
            }
        }

        $recCount = count($records);
        $totalProcessedSoFar = $offset + $recCount;
        $percentText = $totalOdooEmployees > 0 ? " (" . round(($totalProcessedSoFar / $totalOdooEmployees) * 100) . "%)" : "";
        $log('info', "📥 Diterima {$recCount} data karyawan dari Odoo. Total terambil: {$totalProcessedSoFar}" . ($totalOdooEmployees > 0 ? " / {$totalOdooEmployees}" : "") . "{$percentText}...");

        foreach ($records as $rec) {
            try {
                // List of all internal company names
                $allInternalCompanyNames = [
                    'pt arina multi karya',
                    'pt alva karya perkasa',
                    'pt anugrah talenta berkarya',
                    'pt anugrah terpercaya kerja',
                    'pt abadi berkat odelia',
                    'arina multi karya',
                    'alva karya perkasa',
                    'anugrah talenta berkarya',
                    'anugrah terpercaya kerja',
                    'abadi berkat odelia',
                ];

                // Resolve Principal
                $principalId = null;
                $principalName = '';
                if (!empty($rec['principle_id']) && is_array($rec['principle_id'])) {
                    $principalName = trim($rec['principle_id'][1]);
                    $principal = Principal::where('odoo_id', $rec['principle_id'][0])->first();
                    if ($principal) {
                        $principalId = $principal->id;
                    } else {
                        $principal = Principal::create([
                            'odoo_id' => $rec['principle_id'][0],
                            'name' => $principalName,
                            'code' => 'OD-' . $rec['principle_id'][0],
                            'company_id' => $companyId,
                        ]);
                        $principalId = $principal->id;
                    }
                }

                // Resolve Department & Inhouse status — jika prinsiple kosong atau sama dengan company -> Inhouse, else -> Ratecard
                $companyNameLower = strtolower(trim($localCompany->name ?? ''));
                $odooCompanyNameLower = !empty($rec['company_id']) && is_array($rec['company_id']) ? strtolower(trim($rec['company_id'][1])) : '';
                $pNameLower = strtolower(trim($principalName));

                $isInhouse = false;
                if (empty($principalName) || $pNameLower === $companyNameLower || $pNameLower === $odooCompanyNameLower || in_array($pNameLower, $allInternalCompanyNames)) {
                    $isInhouse = true;
                }

                // If inhouse: company and principal MUST BE IDENTICAL (Principal = Company's own principal)
                if ($isInhouse) {
                    $companyPrincipal = Principal::firstOrCreate(
                        ['name' => $localCompany->name],
                        [
                            'code' => 'PRIN-' . ($localCompany->code ?: $companyId),
                            'company_id' => $companyId,
                            'is_active' => true,
                        ]
                    );
                    $principalId = $companyPrincipal->id;
                    $principalName = $companyPrincipal->name;
                }

                $deptName = $isInhouse ? 'Inhouse' : 'Ratecard';
                if (!empty($rec['department_id']) && is_array($rec['department_id'])) {
                    $rawDeptName = trim((string) $rec['department_id'][1]);
                    if (!empty($rawDeptName)) {
                        $deptName = $rawDeptName;
                    }
                }

                $department = Department::firstOrCreate(
                    [
                        'name'         => $deptName,
                        'principal_id' => $principalId,
                    ],
                    [
                        'company_id'          => $companyId,
                        'code'                => 'DEP-' . strtoupper(\Illuminate\Support\Str::random(5)),
                        'is_active'           => true,
                        'has_sales_reporting' => (strtoupper($deptName) === 'SALES'),
                        'cutoff_start_date'   => 26,
                        'working_days'        => ['1', '2', '3', '4', '5'],
                    ]
                );

                if ((!$department->principal_id && $principalId) || (!$department->company_id && $companyId) || empty($department->code)) {
                    $department->update([
                        'principal_id' => $department->principal_id ?: $principalId,
                        'company_id'   => $department->company_id ?: $companyId,
                        'code'         => $department->code ?: ('DEP-' . strtoupper(\Illuminate\Support\Str::random(5))),
                    ]);
                }

                $departmentId = $department->id;

                // Resolve Area (Branch in local db) — auto-create if not found
                $localBranchId = null;
                $areaName = 'Pusat';
                if (!empty($rec['area_id']) && is_array($rec['area_id'])) {
                    $areaName = $rec['area_id'][1];
                    $branch = \App\Models\Branch::firstOrCreate(
                        ['name' => $areaName],
                        ['code' => 'OD-AREA-' . $rec['area_id'][0], 'is_active' => true]
                    );
                    if (empty($branch->code)) {
                        $branch->update(['code' => 'OD-AREA-' . $rec['area_id'][0]]);
                    }
                    $localBranchId = $branch->id;
                }

                // Resolve Position — auto-create if not found, assign principal_id & code
                $positionId = null;
                $posName = 'Staff';
                if (!empty($rec['job_id']) && is_array($rec['job_id'])) {
                    $posName    = $rec['job_id'][1];
                    $position   = Position::firstOrCreate(
                        ['name' => $posName, 'principal_id' => $principalId],
                        [
                            'company_id' => $companyId,
                            'code'       => 'POS-' . strtoupper(\Illuminate\Support\Str::random(5)),
                            'is_active'  => true,
                        ]
                    );
                    
                    // Update principal_id, company_id or code if empty
                    if (!$position->principal_id || !$position->company_id || empty($position->code)) {
                        $position->update([
                            'principal_id' => $position->principal_id ?: $principalId,
                            'company_id'   => $position->company_id ?: $companyId,
                            'code'         => $position->code ?: ('POS-' . strtoupper(\Illuminate\Support\Str::random(5))),
                        ]);
                    }
                    
                    $positionId = $position->id;
                }

                // Helper to clean Odoo XML-RPC values
                $cleanStr = function ($val) {
                    if ($val === null || $val === false || $val === 'false' || $val === 'null') {
                        return null;
                    }
                    $str = trim((string) $val);
                    return $str !== '' ? $str : null;
                };

                $cleanDate = function ($dateVal) use ($cleanStr) {
                    $str = $cleanStr($dateVal);
                    if (!$str || $str === '0000-00-00') {
                        return null;
                    }
                    return preg_match('/^\d{4}-\d{2}-\d{2}/', $str) ? substr($str, 0, 10) : null;
                };

                // Map employment status & active status
                $isActiveInOdoo = true;
                if (isset($rec['active'])) {
                    $parsedActive = filter_var($rec['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($parsedActive !== null) {
                        $isActiveInOdoo = $parsedActive;
                    }
                }

                $departureDate = $cleanDate($rec['departure_date'] ?? null);
                if ($departureDate && strtotime($departureDate) && strtotime($departureDate) <= time()) {
                    $isActiveInOdoo = false;
                }

                $employmentStatus = $isActiveInOdoo ? 'contract' : 'resigned';
                $birthDate = $cleanDate($rec['birthday'] ?? null);
                $joinDate = $cleanDate($rec['first_contract_date'] ?? null);
                $phone = $cleanStr($rec['mobile_phone'] ?? null);
                $email = $cleanStr($rec['private_email'] ?? null) ?: $cleanStr($rec['work_email'] ?? null);

                // Map gender
                $rawGender = strtolower(trim((string)($rec['gender'] ?? '')));
                $gender = ($rawGender === 'male' || $rawGender === 'female') ? $rawGender : null;

                // Employee no — prefer identification_id (NIK / No. KTP)
                $rawNik = $cleanStr($rec['identification_id'] ?? null);
                $rawRegNo = $cleanStr($rec['registration_number'] ?? null);
                $employeeNo = $rawNik ?: ($rawRegNo ?: ('OD-' . $rec['id']));

                // Look up existing employee:
                // Parameter Pencocokan: WAJIB HANYA jika No. KTP / NIK (employee_no) DAN Principal (principal_id) KEDUANYA SAMA!
                $existingEmployees = collect();
                if ($rawNik) {
                    $query = Employee::withTrashed()->where('employee_no', $rawNik);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    } else {
                        $query->whereNull('principal_id');
                    }
                    $existingEmployees = $query->get();
                } elseif ($rawRegNo) {
                    $query = Employee::withTrashed()->where('employee_no', $rawRegNo);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    } else {
                        $query->whereNull('principal_id');
                    }
                    $existingEmployees = $query->get();
                } else {
                    // Fallback jika tidak ada NIK dan NIP di Odoo: gunakan kombinasi odoo_id + company_id + principal_id
                    $query = Employee::withTrashed()
                        ->where('odoo_id', $rec['id'])
                        ->where('company_id', $companyId);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    } else {
                        $query->whereNull('principal_id');
                    }
                    $existingEmployees = $query->get();
                }

                if ($existingEmployees->isNotEmpty()) {
                    // Pick the best primary record to keep & update (prioritaskan yang memiliki foto/device/password)
                    $primary = $existingEmployees->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
                        ?: ($existingEmployees->firstWhere('odoo_id', $rec['id'])
                        ?: $existingEmployees->first());

                    // PROTEKSI AKUN AKTIF LINTAS ENTITAS:
                    if (!$isActiveInOdoo && $primary->is_active && ($primary->principal_id != $principalId || $primary->company_id != $companyId)) {
                        continue;
                    }

                    // If trashed, restore it so it becomes visible in all normal queries
                    if ($primary->trashed()) {
                        $primary->restore();
                    }

                    // If there are duplicate records with this same NIK and same principal, merge and remove the redundant ones
                    $duplicateIds = $existingEmployees->where('id', '!=', $primary->id)
                        ->where('principal_id', $principalId)
                        ->pluck('id')->toArray();
                    if (!empty($duplicateIds)) {
                        $this->mergeDuplicates($primary, $duplicateIds);
                    }

                    $wasActive = $primary->is_active;

                    $updatePayload = [
                        'odoo_id'           => $rec['id'],
                        'company_id'        => $companyId,
                        'principal_id'      => $principalId,
                        'department_id'     => $departmentId,
                        'position_id'       => $positionId,
                        'branch_id'         => $localBranchId,
                        'employee_no'       => $employeeNo,
                        'full_name'         => $rec['name'],
                        'gender'            => $gender,
                        'birth_date'        => $birthDate ?: $primary->birth_date,
                        'join_date'         => $joinDate ?: $primary->join_date,
                        'phone'             => $phone ?: $primary->phone,
                        'email'             => $email ?: $primary->email,
                        'employment_status' => $employmentStatus,
                        'is_active'         => $isActiveInOdoo,
                        'resign_date'       => !$isActiveInOdoo ? ($departureDate ?: ($primary->resign_date ?: now()->toDateString())) : null,
                    ];

                    // Set default password '123456' if employee currently has no password
                    if (empty($primary->password)) {
                        $updatePayload['password'] = \Illuminate\Support\Facades\Hash::make('123456');
                    }

                    $primary->fill($updatePayload);
                    $dirtyAttributes = $primary->getDirty();
                    $isDirty = count($dirtyAttributes) > 0;
                    $primary->save();

                    if (!$isActiveInOdoo && $wasActive) {
                        $resigned++;
                        $resignedEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo];
                        $log('resigned', "🚪 [RESIGN] [{$employeeNo}] {$rec['name']} — {$posName} ({$areaName})", ['nik' => $employeeNo, 'name' => $rec['name']]);
                    } elseif ($isDirty) {
                        $updated++;
                        $updatedEmployees[] = [
                            'name' => $rec['name'],
                            'nik' => $employeeNo,
                            'position' => $posName,
                            'changes' => array_keys($dirtyAttributes)
                        ];
                        $log('updated', "🔄 [UPDATE] [{$employeeNo}] {$rec['name']} — " . implode(', ', array_keys($dirtyAttributes)), ['nik' => $employeeNo, 'name' => $rec['name']]);
                    }
                } else {
                    Employee::create([
                        'odoo_id'           => $rec['id'],
                        'company_id'        => $companyId,
                        'principal_id'      => $principalId,
                        'department_id'     => $departmentId,
                        'position_id'       => $positionId,
                        'branch_id'         => $localBranchId,
                        'employee_no'       => $employeeNo,
                        'full_name'         => $rec['name'],
                        'password'          => \Illuminate\Support\Facades\Hash::make('123456'),
                        'gender'            => $gender,
                        'birth_date'        => $birthDate,
                        'join_date'         => $joinDate,
                        'phone'             => $phone,
                        'email'             => $email,
                        'employment_status' => $employmentStatus,
                        'is_active'         => $isActiveInOdoo,
                        'resign_date'       => !$isActiveInOdoo ? ($departureDate ?: now()->toDateString()) : null,
                    ]);

                    if ($isActiveInOdoo) {
                        $created++;
                        $newEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo, 'position' => $posName];
                        $log('created', "➕ [BARU] [{$employeeNo}] {$rec['name']} — {$posName} ({$areaName})", ['nik' => $employeeNo, 'name' => $rec['name']]);
                    } else {
                        $resigned++;
                        $resignedEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo];
                        $log('resigned', "🚪 [RESIGN BARU] [{$employeeNo}] {$rec['name']} — {$posName} ({$areaName})", ['nik' => $employeeNo, 'name' => $rec['name']]);
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Employee [{$rec['name']}]: " . $e->getMessage();
                $log('error', "⚠️ Error Employee [{$rec['name']}]: " . $e->getMessage());
                Log::error('Odoo Sync Employee Error', ['record' => $rec, 'error' => $e->getMessage()]);
            }
        }

        return [
            'count'    => $recCount,
            'created'  => $created,
            'updated'  => $updated,
            'resigned' => $resigned,
            'errors'   => $errors,
        ];
    }

    /**
     * Full Sync Employees from Odoo hr.employee (loops through all batches).
     */
    public function syncEmployees(int $companyId, ?int $odooCompanyId = null, ?callable $progressCallback = null): array
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        $totalOdooEmployees = $this->countOdooEmployees($companyId, $odooCompanyId);
        $limit = 250;
        $offset = 0;
        $batchNum = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalResigned = 0;
        $allErrors = [];

        if ($totalOdooEmployees > 0) {
            $totalBatches = ceil($totalOdooEmployees / $limit);
            $log('info', "👥 Terdeteksi total {$totalOdooEmployees} data karyawan di Odoo. Memproses dalam {$totalBatches} batch (Ukuran per batch: {$limit})...");
        }

        do {
            $batchNum++;
            $batchRes = $this->syncEmployeesBatch($companyId, $offset, $limit, $odooCompanyId, $progressCallback, $batchNum, $totalOdooEmployees);
            $totalCreated += $batchRes['created'];
            $totalUpdated += $batchRes['updated'];
            $totalResigned += $batchRes['resigned'];
            $allErrors = array_merge($allErrors, $batchRes['errors']);
            $offset += $limit;

            $currentTotalProcessed = min($offset, $totalOdooEmployees > 0 ? $totalOdooEmployees : $offset);
            $progressPercent = $totalOdooEmployees > 0 ? min(100, round(($currentTotalProcessed / $totalOdooEmployees) * 100)) : 50;

            $log('progress', "📊 Status Progres: {$currentTotalProcessed}" . ($totalOdooEmployees > 0 ? " / {$totalOdooEmployees} ({$progressPercent}%)" : "") . " (Baru: {$totalCreated}, Update: {$totalUpdated}, Resign: {$totalResigned})", [
                'processed' => $currentTotalProcessed,
                'total'     => $totalOdooEmployees,
                'progress'  => $progressPercent,
                'created'   => $totalCreated,
                'updated'   => $totalUpdated,
                'resigned'  => $totalResigned,
            ]);
        } while ($batchRes['count'] == $limit);

        $log('summary', "👥 Selesai Sync Employees! Total Baru: {$totalCreated} | Diperbarui: {$totalUpdated} | Resign: {$totalResigned}" . (count($allErrors) > 0 ? " | Error: " . count($allErrors) : ""), [
            'created'  => $totalCreated,
            'updated'  => $totalUpdated,
            'resigned' => $totalResigned,
            'errors'   => count($allErrors),
        ]);

        return [
            'created'  => $totalCreated,
            'updated'  => $totalUpdated,
            'resigned' => $totalResigned,
            'errors'   => $allErrors,
        ];
    }

    /**
     * Run full sync for all companies with valid Odoo configuration.
     * Companies without configuration will be automatically skipped.
     * @param string $triggerType - 'cron' or 'manual'
     * @param string|null $batchId - Batch identifier
     * @param callable|null $progressCallback - Optional callback for live progress streaming
     */
    public static function syncAllConfiguredCompanies(string $triggerType = 'cron', ?string $batchId = null, ?callable $progressCallback = null): array
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        $batchId = $batchId ?: ('SYNC-' . date('Ymd-His') . '-' . \Illuminate\Support\Str::random(6));

        $companies = Company::where('is_active', true)
            ->whereNotNull('odoo_url')
            ->whereNotNull('odoo_db')
            ->whereNotNull('odoo_username')
            ->whereNotNull('odoo_api_key')
            ->where('odoo_url', '!=', '')
            ->where('odoo_db', '!=', '')
            ->where('odoo_username', '!=', '')
            ->where('odoo_api_key', '!=', '')
            ->orderBy('id')
            ->get();

        $totalCompanies = $companies->count();
        $log('info', "🏢 Ditemukan {$totalCompanies} perusahaan aktif dengan konfigurasi Odoo lengkap.");

        $results = [
            'batch_id' => $batchId,
            'companies_count' => $totalCompanies,
            'companies' => [],
            'total_created' => 0,
            'total_updated' => 0,
            'total_resigned' => 0,
            'total_employees' => 0,
            'errors' => [],
        ];

        $companyIndex = 0;
        foreach ($companies as $company) {
            $companyIndex++;
            $log('company_start', "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $log('company_start', "🏢 [{$companyIndex}/{$totalCompanies}] MEMULAI SINKRONISASI: {$company->name} (DB: {$company->odoo_db})", [
                'company_id' => $company->id,
                'name' => $company->name
            ]);

            $companyResult = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'company_code' => $company->code ?? strtoupper(substr($company->name, 0, 4)),
                'created' => 0,
                'updated' => 0,
                'resigned' => 0,
                'total_employees' => 0,
                'status' => 'success',
                'errors' => [],
            ];

            try {
                $service = self::fromCompany($company);
                if (!$service) {
                    $log('warning', "⚠️ Konfigurasi Odoo untuk {$company->name} tidak lengkap, dilewati.");
                    continue;
                }

                // 1. Sync Principals
                $log('info', "--- Sync Principals [{$company->name}] ---");
                $pResult = $service->syncPrincipals($company->id, null, $progressCallback);

                // 2. Sync Employees
                $log('info', "--- Sync Employees [{$company->name}] ---");
                $eResult = $service->syncEmployees($company->id, null, $progressCallback);

                $companyResult['created'] = $eResult['created'] ?? 0;
                $companyResult['updated'] = $eResult['updated'] ?? 0;
                $companyResult['resigned'] = $eResult['resigned'] ?? 0;
                $companyResult['errors'] = array_merge($pResult['errors'] ?? [], $eResult['errors'] ?? []);

                $totalActive = Employee::where('company_id', $company->id)->where('is_active', true)->count();
                $companyResult['total_employees'] = $totalActive;

                if (!empty($companyResult['errors'])) {
                    $companyResult['status'] = 'partial';
                }

                // Save log to odoo_sync_logs table
                \App\Models\OdooSyncLog::create([
                    'batch_id' => $batchId,
                    'company_id' => $company->id,
                    'sync_type' => 'all',
                    'trigger_type' => $triggerType,
                    'status' => $companyResult['status'],
                    'new_count' => $companyResult['created'],
                    'update_count' => $companyResult['updated'],
                    'resign_count' => $companyResult['resigned'],
                    'total_employee_count' => $totalActive,
                    'details' => [
                        'principals' => $pResult,
                        'new_employees' => $eResult['newEmployees'] ?? [],
                        'updated_employees' => $eResult['updatedEmployees'] ?? [],
                        'resigned_employees' => $eResult['resignedEmployees'] ?? [],
                        'errors' => $companyResult['errors'],
                    ],
                ]);

                $results['total_created'] += $companyResult['created'];
                $results['total_updated'] += $companyResult['updated'];
                $results['total_resigned'] += $companyResult['resigned'];
                $results['total_employees'] += $totalActive;

                $log('company_end', "✅ SELESAI SINKRONISASI: {$company->name} — Baru: {$companyResult['created']} | Update: {$companyResult['updated']} | Resign: {$companyResult['resigned']} | Total Karyawan: {$totalActive}");

            } catch (\Exception $e) {
                $companyResult['status'] = 'failed';
                $companyResult['errors'][] = $e->getMessage();
                $results['errors'][] = "Company [{$company->name}]: " . $e->getMessage();

                $log('error', "❌ GAGAL SINKRONISASI: {$company->name} — " . $e->getMessage());

                \App\Models\OdooSyncLog::create([
                    'batch_id' => $batchId,
                    'company_id' => $company->id,
                    'sync_type' => 'all',
                    'trigger_type' => $triggerType,
                    'status' => 'failed',
                    'new_count' => 0,
                    'update_count' => 0,
                    'resign_count' => 0,
                    'total_employee_count' => Employee::where('company_id', $company->id)->where('is_active', true)->count(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            $results['companies'][$company->id] = $companyResult;
        }

        $log('summary', "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $log('summary', "🎉 SELURUH PERUSAHAAN SELESAI DISINKRONISASI ({$totalCompanies} Entitas)!\n" .
            "   ➕ Total Baru: {$results['total_created']}\n" .
            "   🔄 Total Update: {$results['total_updated']}\n" .
            "   🚪 Total Resign: {$results['total_resigned']}\n" .
            "   👥 Total Karyawan Aktif: {$results['total_employees']}");

        return $results;
    }

    /**
     * Merge and clean up duplicate employee records.
     */
    public function mergeDuplicates(Employee $primary, array $duplicateIds): void
    {
        if (empty($duplicateIds)) {
            return;
        }

        $tables = [
            ['table' => 'attendance_logs', 'column' => 'employee_id'],
            ['table' => 'leave_requests', 'column' => 'employee_id'],
            ['table' => 'extra_hours', 'column' => 'employee_id'],
            ['table' => 'bap_requests', 'column' => 'employee_id'],
            ['table' => 'itineraries', 'column' => 'employee_id'],
            ['table' => 'sales_reports', 'column' => 'employee_id'],
            ['table' => 'work_targets', 'column' => 'employee_id'],
            ['table' => 'payslips', 'column' => 'employee_id'],
            ['table' => 'tracking_histories', 'column' => 'employee_id'],
            ['table' => 'report_submissions', 'column' => 'employee_id'],
            ['table' => 'meeting_participants', 'column' => 'employee_id'],
            ['table' => 'meeting_attendances', 'column' => 'employee_id'],
            ['table' => 'location_requests', 'column' => 'employee_id'],
            ['table' => 'report_template_assignments', 'column' => 'employee_id'],
            ['table' => 'employees', 'column' => 'supervisor_id'],
        ];

        // Safely merge attendances (unique by employee_id + attendance_date)
        $this->safeMerge('attendances', 'employee_id', $primary->id, $duplicateIds, 'attendance_date');
        
        // Safely merge employee_schedules (unique by employee_id + date)
        $this->safeMerge('employee_schedules', 'employee_id', $primary->id, $duplicateIds, 'date');


        foreach ($tables as $t) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable($t['table'])) {
                    \Illuminate\Support\Facades\DB::table($t['table'])
                        ->whereIn($t['column'], $duplicateIds)
                        ->update([$t['column'] => $primary->id]);
                }
            } catch (\Exception $e) {
                Log::warning("OdooSync merge duplicate table {$t['table']} error: " . $e->getMessage());
            }
        }

        $duplicates = Employee::withTrashed()->whereIn('id', $duplicateIds)->get();
        foreach ($duplicates as $dup) {
            if (empty($primary->photo) && !empty($dup->photo)) {
                $primary->photo = $dup->photo;
            }
            if (empty($primary->password) && !empty($dup->password)) {
                $primary->password = $dup->password;
            }
            if (empty($primary->device_id) && !empty($dup->device_id)) {
                $primary->device_id = $dup->device_id;
                $primary->device_name = $dup->device_name;
            }
            if (empty($primary->user_id) && !empty($dup->user_id)) {
                $primary->user_id = $dup->user_id;
            }
            $primary->save();

            try {
                $dup->forceDelete();
            } catch (\Exception $e) {
                Log::warning("OdooSync delete duplicate employee ID {$dup->id} error: " . $e->getMessage());
            }
        }
    }

    private function safeMerge($tableName, $foreignKey, $primaryId, $dupIds, $uniqueDateColumn): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            return;
        }

        foreach ($dupIds as $dupId) {
            $orphanRecords = \Illuminate\Support\Facades\DB::table($tableName)->where($foreignKey, $dupId)->get();
            foreach ($orphanRecords as $record) {
                $exists = \Illuminate\Support\Facades\DB::table($tableName)
                    ->where($foreignKey, $primaryId)
                    ->where($uniqueDateColumn, $record->{$uniqueDateColumn})
                    ->exists();

                if (!$exists) {
                    try {
                        \Illuminate\Support\Facades\DB::table($tableName)->where('id', $record->id)->update([
                            $foreignKey => $primaryId
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to merge {$tableName} ID {$record->id}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Clean up all duplicate employees in database based on NIK (employee_no) and Principal (principal_id).
     */
    public static function cleanupAllDuplicateEmployees(?callable $progressCallback = null): int
    {
        $log = function(string $type, string $message, ?array $meta = null) use ($progressCallback) {
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $type, $message, $meta);
            }
        };

        $log('info', "🔍 Memindai seluruh database untuk mendeteksi NIK ganda pada Prinsiple yang sama...");

        $duplicateGroups = Employee::withTrashed()
            ->select('employee_no', 'principal_id')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->where('employee_no', 'not like', 'OD-%')
            ->whereNotNull('principal_id')
            ->groupBy('employee_no', 'principal_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $groupCount = $duplicateGroups->count();
        if ($groupCount === 0) {
            $log('success', "✨ Bersih! Tidak ditemukan kelompok NIK duplikat.");
            return 0;
        }

        $log('warning', "⚠️ Ditemukan {$groupCount} kelompok data NIK ganda. Memulai penggabungan data riwayat...");

        $totalCleaned = 0;
        $index = 0;
        foreach ($duplicateGroups as $group) {
            $index++;
            $records = Employee::withTrashed()
                ->where('employee_no', $group->employee_no)
                ->where('principal_id', $group->principal_id)
                ->get();

            if ($records->count() > 1) {
                $primary = $records->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
                    ?: ($records->firstWhere('odoo_id', '!=', null) ?: $records->first());

                $dupIds = $records->where('id', '!=', $primary->id)->pluck('id')->toArray();
                if (!empty($dupIds)) {
                    $log('item', "🧹 [{$index}/{$groupCount}] Menggabungkan {$records->count()} baris data NIK [{$group->employee_no}] {$primary->full_name} -> Akun Utama ID: {$primary->id}");
                    $service = new self('', '', '', '');
                    $service->mergeDuplicates($primary, $dupIds);
                    $totalCleaned += count($dupIds);
                }
            }
        }

        $log('success', "🎉 Pembersihan selesai! Total {$totalCleaned} baris data duplikat berhasil digabungkan dan dibersihkan.");
        return $totalCleaned;
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function mapEmploymentStatus(string $odooType): string
    {
        switch ($odooType) {
            case 'employee':
                return 'permanent';
            case 'student':
                return 'intern';
            case 'freelance':
            default:
                return 'contract';
        }
    }

    /**
     * Perform an Odoo XML-RPC call.
     */
    private function xmlRpcCall(string $path, string $method, array $params): mixed
    {
        $endpoint = $this->url . $path;

        // Build XML-RPC request
        $xmlParams = array_map([$this, 'phpToXmlRpc'], $params);
        $xmlBody   = '<?xml version="1.0"?><methodCall><methodName>' . htmlspecialchars($method) . '</methodName><params>';
        foreach ($xmlParams as $param) {
            $xmlBody .= '<param><value>' . $param . '</value></param>';
        }
        $xmlBody .= '</params></methodCall>';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xmlBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: text/xml', 'Content-Length: ' . strlen($xmlBody)],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawResponse = curl_exec($ch);
        $error       = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: $error");
        }

        return $this->parseXmlRpcResponse($rawResponse);
    }

    private function phpToXmlRpc(mixed $value): string
    {
        if (is_bool($value)) {
            return '<boolean>' . ($value ? '1' : '0') . '</boolean>';
        }
        if (is_int($value)) {
            return '<int>' . $value . '</int>';
        }
        if (is_float($value)) {
            return '<double>' . $value . '</double>';
        }
        if (is_null($value) || $value === false) {
            return '<boolean>0</boolean>';
        }
        if (is_string($value)) {
            return '<string>' . htmlspecialchars($value) . '</string>';
        }
        if (is_array($value)) {
            // Check if associative (struct) or sequential (array)
            if (array_values($value) !== $value) {
                // Struct
                $members = '';
                foreach ($value as $k => $v) {
                    $members .= '<member><name>' . htmlspecialchars($k) . '</name><value>' . $this->phpToXmlRpc($v) . '</value></member>';
                }
                return '<struct>' . $members . '</struct>';
            }
            // Array
            $data = '';
            foreach ($value as $v) {
                $data .= '<value>' . $this->phpToXmlRpc($v) . '</value>';
            }
            return '<array><data>' . $data . '</data></array>';
        }
        return '<string>' . htmlspecialchars((string) $value) . '</string>';
    }

    private function parseXmlRpcResponse(string $rawResponse): mixed
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawResponse);

        if ($xml === false) {
            throw new \Exception("Failed to parse XML-RPC response.");
        }

        // Check for fault
        if (isset($xml->fault)) {
            $fault = $this->xmlRpcToPHP($xml->fault->value);
            throw new \Exception("Odoo XML-RPC Fault [{$fault['faultCode']}]: {$fault['faultString']}");
        }

        return $this->xmlRpcToPHP($xml->params->param->value);
    }

    private function xmlRpcToPHP(\SimpleXMLElement $value): mixed
    {
        if (isset($value->array)) {
            $result = [];
            foreach ($value->array->data->value as $item) {
                $result[] = $this->xmlRpcToPHP($item);
            }
            return $result;
        }
        if (isset($value->struct)) {
            $result = [];
            foreach ($value->struct->member as $member) {
                $result[(string) $member->name] = $this->xmlRpcToPHP($member->value);
            }
            return $result;
        }
        if (isset($value->int))     return (int)    $value->int;
        if (isset($value->i4))      return (int)    $value->i4;
        if (isset($value->i8))      return (int)    $value->i8;
        if (isset($value->double))  return (float)  $value->double;
        if (isset($value->boolean)) return (bool)   ((int) $value->boolean);
        if (isset($value->string))  return (string) $value->string;
        if (isset($value->nil))     return null;
        // Plain text node
        return (string) $value;
    }
}
