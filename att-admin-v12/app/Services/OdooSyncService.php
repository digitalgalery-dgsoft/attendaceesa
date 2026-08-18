<?php

namespace App\Services;

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
        $this->url      = rtrim($url, '/');
        $this->db       = $db;
        $this->username = $username;
        $this->apiKey   = $apiKey;
    }

    /**
     * Build an Odoo XML-RPC service instance from app settings.
     */
    public static function fromSettings(): ?self
    {
        $setting = Setting::first();
        if (!$setting || !$setting->odoo_sync_enabled || !$setting->odoo_url || !$setting->odoo_db || !$setting->odoo_username || !$setting->odoo_api_key) {
            return null;
        }
        return new self($setting->odoo_url, $setting->odoo_db, $setting->odoo_username, $setting->odoo_api_key);
    }

    /**
     * Authenticate with Odoo and return uid.
     */
    public function authenticate(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        $response = $this->xmlRpcCall('/xmlrpc/2/common', 'authenticate', [
            $this->db,
            $this->username,
            $this->apiKey,
            [],
        ]);

        if (!$response || !is_int($response)) {
            throw new \Exception("Odoo authentication failed. Check URL, DB, Username, and API Key.");
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

        $records = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            'res.partner', 'search_read',
            [$domain],
            ['fields' => ['id', 'name', 'code_principal', 'ref', 'active'], 'limit' => 500],
        ]);

        $created  = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($records as $rec) {
            try {
                $code = $rec['code_principal'] ?? $rec['ref'] ?? null;

                $principal = Principal::updateOrCreate(
                    ['odoo_id' => $rec['id']],
                    [
                        'name'       => $rec['name'],
                        'code'       => $code ?: ('OD-' . $rec['id']),
                        'company_id' => $companyId,
                    ]
                );

                if ($principal->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Principal [{$rec['name']}]: " . $e->getMessage();
                Log::error('Odoo Sync Principal Error', ['record' => $rec, 'error' => $e->getMessage()]);
            }
        }

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

        $domain = [['active', '=', true]];
        if ($odooCompanyId) {
            $domain[] = ['company_id', '=', $odooCompanyId];
        }

        $records = $this->xmlRpcCall('/xmlrpc/2/object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            'hr.employee', 'search_read',
            [$domain],
            [
                'fields' => [
                    'id', 'name', 'registration_number', 'identification_id',
                    'mobile_phone', 'work_email', 'private_email', 'gender', 'birthday',
                    'department_id', 'job_id', 'principle_id', 'first_contract_date',
                    'area_id', 'company_id', 'active',
                ],
                'limit' => 1000,
            ],
        ]);

        $localCompany = Company::find($companyId);
        $created  = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($records as $rec) {
            try {
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

                // Resolve Department — jika prinsiple = company -> Inhouse, else -> Ratecard
                $companyName = strtolower(trim($localCompany->name ?? ''));
                $odooCompanyName = !empty($rec['company_id']) && is_array($rec['company_id']) ? strtolower(trim($rec['company_id'][1])) : '';
                
                $isInhouse = false;
                if ($principalName !== '') {
                    $pNameLower = strtolower($principalName);
                    if ($pNameLower === $companyName || $pNameLower === $odooCompanyName) {
                        $isInhouse = true;
                    }
                }

                $deptName = $isInhouse ? 'Inhouse' : 'Ratecard';
                $department = Department::firstOrCreate(
                    ['name' => $deptName, 'company_id' => $companyId],
                    ['is_active' => true]
                );
                $departmentId = $department->id;

                // Resolve Area — auto-create if not found
                $localAreaId = null;
                if (!empty($rec['area_id']) && is_array($rec['area_id'])) {
                    $areaName = $rec['area_id'][1];
                    $area = Area::firstOrCreate(
                        ['name' => $areaName],
                        ['code' => 'OD-AREA-' . $rec['area_id'][0]]
                    );
                    $localAreaId = $area->id;
                }

                // Resolve Position — auto-create if not found, assign principal_id
                $positionId = null;
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

                // Map employment status
                $employmentStatus = 'contract';

                // Map gender
                $gender = match ($rec['gender'] ?? '') {
                    'male'   => 'male',
                    'female' => 'female',
                    default  => null,
                };

                // Employee no — prefer identification_id (NIK)
                $employeeNo = $rec['identification_id'] ?: ('OD-' . $rec['id']);

                $employee = Employee::withTrashed()->updateOrCreate(
                    ['odoo_id' => $rec['id']],
                    [
                        'company_id'        => $companyId,
                        'department_id'     => $departmentId,
                        'position_id'       => $positionId,
                        'area_id'           => $localAreaId,
                        'employee_no'       => $employeeNo,
                        'full_name'         => $rec['name'],
                        'gender'            => $gender,
                        'birth_date'        => $rec['birthday'] ?: null,
                        'join_date'         => $rec['first_contract_date'] ?: null,
                        'phone'             => $rec['mobile_phone'] ?: null,
                        'email'             => $rec['private_email'] ?: ($rec['work_email'] ?: null),
                        'employment_status' => $employmentStatus,
                        'is_active'         => (bool) $rec['active'],
                        'deleted_at'        => null, // restore if soft-deleted
                    ]
                );

                if ($employee->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Employee [{$rec['name']}]: " . $e->getMessage();
                Log::error('Odoo Sync Employee Error', ['record' => $rec, 'error' => $e->getMessage()]);
            }
        }

        return compact('created', 'updated', 'errors');
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
