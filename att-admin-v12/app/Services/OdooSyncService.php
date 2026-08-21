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
    public function testConnection(): array
    {
        $version = $this->xmlRpcCall('/xmlrpc/2/common', 'version', []);
        $uid     = $this->authenticate();

        return [
            'success'       => true,
            'uid'           => $uid,
            'server_version'=> $version['server_version'] ?? 'unknown',
        ];
    }

    /**
     * Sync Principals (res.partner with is_principal = True) from Odoo.
     * @param int $companyId - Local company ID to associate principals with
     * @param int|null $odooCompanyId - Odoo company_id to filter by (optional)
     */
    public function syncPrincipals(int $companyId, ?int $odooCompanyId = null): array
    {
        $uid = $this->authenticate();

        $domain = [['is_principal', '=', true]];
        if ($odooCompanyId) {
            $domain[] = ['company_id', '=', $odooCompanyId];
        }

        $created  = 0;
        $updated  = 0;
        $errors   = [];
        $offset   = 0;
        $limit    = 500;

        do {
            $records = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
                $this->db, $uid, $this->apiKey,
                'res.partner', 'search_read',
                [$domain],
                ['fields' => ['id', 'name', 'code_principal', 'ref', 'active'], 'limit' => $limit, 'offset' => $offset],
            ]);

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
                }
            } catch (\Exception $e) {
                $errors[] = "Principal [{$rec['name']}]: " . $e->getMessage();
                Log::error('Odoo Sync Principal Error', ['record' => $rec, 'error' => $e->getMessage()]);
            }
        }
            $offset += $limit;
        } while (count($records) == $limit);

        return compact('created', 'updated', 'errors');
    }

    /**
     * Sync Employees from Odoo hr.employee.
     * @param int $companyId - Local company ID to associate employees with
     * @param int|null $odooCompanyId - Odoo company_id to filter by
     */
    public function syncEmployees(int $companyId, ?int $odooCompanyId = null): array
    {
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
        $offset   = 0;
        $limit    = 500;

        do {
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
                    ],
                    'context' => ['active_test' => false],
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

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

                if ((!$department->principal_id && $principalId) || (!$department->company_id && $companyId)) {
                    $department->update([
                        'principal_id' => $department->principal_id ?: $principalId,
                        'company_id'   => $department->company_id ?: $companyId,
                    ]);
                }

                $departmentId = $department->id;

                // Resolve Area (Branch in local db) — auto-create if not found
                $localBranchId = null;
                if (!empty($rec['area_id']) && is_array($rec['area_id'])) {
                    $areaName = $rec['area_id'][1];
                    $branch = \App\Models\Branch::firstOrCreate(
                        ['name' => $areaName],
                        ['code' => 'OD-AREA-' . $rec['area_id'][0], 'is_active' => true]
                    );
                    $localBranchId = $branch->id;
                }

                // Resolve Position — auto-create if not found, assign principal_id
                $positionId = null;
                $posName = '';
                if (!empty($rec['job_id']) && is_array($rec['job_id'])) {
                    $posName    = $rec['job_id'][1];
                    $position   = Position::firstOrCreate(
                        ['name' => $posName, 'company_id' => $companyId],
                        ['is_active' => true, 'principal_id' => $principalId]
                    );
                    
                    // Update principal_id if it was previously empty
                    if (!$position->principal_id && $principalId) {
                        $position->update(['principal_id' => $principalId]);
                    }
                    
                    $positionId = $position->id;
                }

                // Map employment status & active status
                $isActiveInOdoo = isset($rec['active']) ? (bool) $rec['active'] : true;
                if (!empty($rec['departure_date'])) {
                    $isActiveInOdoo = false;
                }
                $employmentStatus = $isActiveInOdoo ? 'contract' : 'resigned';

                // Map gender
                $gender = match ($rec['gender'] ?? '') {
                    'male'   => 'male',
                    'female' => 'female',
                    default  => null,
                };

                // Employee no — prefer identification_id (NIK / No. KTP)
                $rawNik = !empty($rec['identification_id']) ? trim((string) $rec['identification_id']) : null;
                $rawRegNo = !empty($rec['registration_number']) ? trim((string) $rec['registration_number']) : null;
                $employeeNo = $rawNik ?: ($rawRegNo ?: ('OD-' . $rec['id']));

                // Look up existing employee:
                // Parameter Pencocokan: WAJIB HANYA jika NIK (employee_no) DAN Principal (principal_id) KEDUANYA SAMA!
                $existingEmployees = collect();
                if ($rawNik) {
                    $query = Employee::withTrashed()->where('employee_no', $rawNik);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    }
                    $existingEmployees = $query->get();
                } elseif ($rawRegNo) {
                    $query = Employee::withTrashed()->where('employee_no', $rawRegNo);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    }
                    $existingEmployees = $query->get();
                } else {
                    // Fallback jika tidak ada NIK dan NIP di Odoo: gunakan kombinasi ketat odoo_id + company_id + principal_id
                    $query = Employee::withTrashed()
                        ->where('odoo_id', $rec['id'])
                        ->where('company_id', $companyId);
                    if ($principalId) {
                        $query->where('principal_id', $principalId);
                    }
                    $existingEmployees = $query->get();
                }

                if ($existingEmployees->isNotEmpty()) {
                    // Pick the best primary record to keep & update (prioritaskan yang memiliki foto/device/password)
                    $primary = $existingEmployees->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
                        ?: ($existingEmployees->firstWhere('odoo_id', $rec['id'])
                        ?: $existingEmployees->first());

                    // PROTEKSI AKUN AKTIF LINTAS ENTITAS:
                    // Jika data Odoo yang sedang diproses ini NON-AKTIF / RESIGN ($isActiveInOdoo == false),
                    // TETAPI karyawan ini di database saat ini berstatus AKTIF ($primary->is_active == true)
                    // dan berada di Principal/Company LAIN ($primary->principal_id != $principalId || $primary->company_id != $companyId):
                    // MAKA: JANGAN ubah record aktifnya menjadi resign! (Abaikan data arsip/resign dari entitas lamanya).
                    if (!$isActiveInOdoo && $primary->is_active && ($primary->principal_id != $principalId || $primary->company_id != $companyId)) {
                        continue;
                    }

                    // If there are duplicate records with this same NIK and same principal, merge and remove the redundant ones
                    $duplicateIds = $existingEmployees->where('id', '!=', $primary->id)
                        ->where('principal_id', $principalId)
                        ->pluck('id')->toArray();
                    if (!empty($duplicateIds)) {
                        $this->mergeDuplicates($primary, $duplicateIds);
                    }

                    $wasActive = $primary->is_active;
                    $wasTrashed = $primary->trashed();

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
                        'birth_date'        => $rec['birthday'] ?: $primary->birth_date,
                        'join_date'         => $rec['first_contract_date'] ?: $primary->join_date,
                        'phone'             => $rec['mobile_phone'] ?: $primary->phone,
                        'email'             => $rec['private_email'] ?: ($rec['work_email'] ?: $primary->email),
                        'employment_status' => $employmentStatus,
                        'is_active'         => $isActiveInOdoo,
                        'resign_date'       => !$isActiveInOdoo ? ($primary->resign_date ?: now()->toDateString()) : null,
                    ];

                    // Set default password '123456' if employee currently has no password
                    if (empty($primary->password)) {
                        $updatePayload['password'] = \Illuminate\Support\Facades\Hash::make('123456');
                    }

                    $primary->fill($updatePayload);
                    $dirtyAttributes = $primary->getDirty();
                    $isDirty = count($dirtyAttributes) > 0 || $wasTrashed;

                    if ($wasTrashed) {
                        $primary->deleted_at = null;
                    }
                    $primary->save();

                    if (!$isActiveInOdoo && $wasActive) {
                        $resigned++;
                        $resignedEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo];
                    } elseif ($isDirty) {
                        $updated++;
                        $updatedEmployees[] = [
                            'name' => $rec['name'],
                            'nik' => $employeeNo,
                            'position' => $posName,
                            'changes' => array_keys($dirtyAttributes)
                        ];
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
                        'birth_date'        => $rec['birthday'] ?: null,
                        'join_date'         => $rec['first_contract_date'] ?: null,
                        'phone'             => $rec['mobile_phone'] ?: null,
                        'email'             => $rec['private_email'] ?: ($rec['work_email'] ?: null),
                        'employment_status' => $employmentStatus,
                        'is_active'         => $isActiveInOdoo,
                        'resign_date'       => !$isActiveInOdoo ? now()->toDateString() : null,
                    ]);

                    if ($isActiveInOdoo) {
                        $created++;
                        $newEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo, 'position' => $posName];
                    } else {
                        $resigned++;
                        $resignedEmployees[] = ['name' => $rec['name'], 'nik' => $employeeNo];
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Employee [{$rec['name']}]: " . $e->getMessage();
                Log::error('Odoo Sync Employee Error', ['record' => $rec, 'error' => $e->getMessage()]);
            }
        }
            $offset += $limit;
        } while (count($records) == $limit);

        return compact(
            'created',
            'updated',
            'resigned',
            'errors',
            'newEmployees',
            'updatedEmployees',
            'resignedEmployees'
        );
    }

    /**
     * Run full sync for all companies with valid Odoo configuration.
     * Companies without configuration will be automatically skipped.
     */
    public static function syncAllConfiguredCompanies(string $triggerType = 'cron', ?string $batchId = null): array
    {
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

        $results = [
            'batch_id' => $batchId,
            'companies_count' => $companies->count(),
            'companies' => [],
            'total_created' => 0,
            'total_updated' => 0,
            'total_resigned' => 0,
            'total_employees' => 0,
            'errors' => [],
        ];

        foreach ($companies as $company) {
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
                    continue;
                }

                // 1. Sync Principals
                $pResult = $service->syncPrincipals($company->id);

                // 2. Sync Employees
                $eResult = $service->syncEmployees($company->id);

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

            } catch (\Exception $e) {
                $companyResult['status'] = 'failed';
                $companyResult['errors'][] = $e->getMessage();
                $results['errors'][] = "Company [{$company->name}]: " . $e->getMessage();

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
            ['table' => 'attendances', 'column' => 'employee_id'],
            ['table' => 'attendance_logs', 'column' => 'employee_id'],
            ['table' => 'leave_requests', 'column' => 'employee_id'],
            ['table' => 'extra_hours', 'column' => 'employee_id'],
            ['table' => 'bap_requests', 'column' => 'employee_id'],
            ['table' => 'itineraries', 'column' => 'employee_id'],
            ['table' => 'sales_reports', 'column' => 'employee_id'],
            ['table' => 'work_targets', 'column' => 'employee_id'],
            ['table' => 'payslips', 'column' => 'employee_id'],
            ['table' => 'tracking_histories', 'column' => 'employee_id'],
            ['table' => 'employees', 'column' => 'supervisor_id'],
        ];

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

    /**
     * Clean up all duplicate employees in database based on NIK (employee_no) and Principal (principal_id).
     */
    public static function cleanupAllDuplicateEmployees(): int
    {
        $duplicateGroups = Employee::withTrashed()
            ->select('employee_no', 'principal_id')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->where('employee_no', 'not like', 'OD-%')
            ->whereNotNull('principal_id')
            ->groupBy('employee_no', 'principal_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $totalCleaned = 0;
        foreach ($duplicateGroups as $group) {
            $records = Employee::withTrashed()
                ->where('employee_no', $group->employee_no)
                ->where('principal_id', $group->principal_id)
                ->get();

            if ($records->count() > 1) {
                $primary = $records->first(fn ($e) => !empty($e->photo) || !empty($e->device_id) || !empty($e->password))
                    ?: ($records->firstWhere('odoo_id', '!=', null) ?: $records->first());

                $dupIds = $records->where('id', '!=', $primary->id)->pluck('id')->toArray();
                if (!empty($dupIds)) {
                    $service = new self('', '', '', '');
                    $service->mergeDuplicates($primary, $dupIds);
                    $totalCleaned += count($dupIds);
                }
            }
        }

        return $totalCleaned;
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function mapEmploymentStatus(string $odooType): string
    {
        return match ($odooType) {
            'employee'  => 'permanent',
            'student'   => 'intern',
            'freelance' => 'contract',
            default     => 'contract',
        };
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
            CURLOPT_TIMEOUT        => 60,
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
