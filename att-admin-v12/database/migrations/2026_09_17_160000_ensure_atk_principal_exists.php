<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Company;
use App\Models\Principal;
use App\Models\Employee;
use App\Models\Department;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure all companies have an active Inhouse Principal record
        $allCompanies = Company::all();
        $companyPrincipalMap = [];

        foreach ($allCompanies as $comp) {
            $compNameClean = trim($comp->name);
            $prin = Principal::where('company_id', $comp->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($compNameClean)])
                ->first();

            if (!$prin) {
                $prin = Principal::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($compNameClean)])->first();
            }

            if (!$prin) {
                // Determine appropriate code
                $code = '300';
                if (stripos($compNameClean, 'TERPERCAYA') !== false) {
                    $code = '300';
                } elseif (stripos($compNameClean, 'TALENTA') !== false) {
                    $code = '500';
                } elseif (stripos($compNameClean, 'ODELIA') !== false) {
                    $code = '400';
                } elseif (stripos($compNameClean, 'ARINA') !== false) {
                    $code = '100';
                } elseif (stripos($compNameClean, 'ALVA') !== false) {
                    $code = '200';
                } else {
                    $code = 'PRIN-' . ($comp->code ?: $comp->id);
                }

                if (Principal::where('code', $code)->exists()) {
                    $code = 'PRIN-' . ($comp->code ?: $comp->id);
                }

                $prin = Principal::create([
                    'name' => $compNameClean,
                    'code' => $code,
                    'company_id' => $comp->id,
                    'is_active' => true,
                    'theme_color' => '#0F52BA',
                ]);
            } else {
                $prin->update([
                    'company_id' => $comp->id,
                    'is_active' => true,
                ]);
            }

            $companyPrincipalMap[$comp->id] = $prin->id;
        }

        // 2. Re-assign Inhouse employees of PT ANUGRAH TERPERCAYA KERJA (Company ID 1)
        // Previous bug caused Company 1 inhouse employees to be assigned to Principal 37 (PT ANUGRAH TALENTA BERKARYA)
        $atkCompany = Company::whereRaw('LOWER(name) LIKE ?', ['%anugrah terpercaya kerja%'])
            ->orWhere('id', 1)
            ->first();

        if ($atkCompany && isset($companyPrincipalMap[$atkCompany->id])) {
            $atkPrincipalId = $companyPrincipalMap[$atkCompany->id];
            $inhouseDeptIds = Department::whereRaw('LOWER(name) LIKE ?', ['%inhouse%'])->pluck('id')->toArray();

            // Re-assign employees who belong to Company 1 and either have inhouse dept or were erroneously pointing to principal 37
            Employee::where('company_id', $atkCompany->id)
                ->where(function ($q) use ($inhouseDeptIds) {
                    $q->where('principal_id', 37) // Was misassigned to ATB
                      ->orWhereNull('principal_id');
                    if (!empty($inhouseDeptIds)) {
                        $q->orWhereIn('department_id', $inhouseDeptIds);
                    }
                })
                ->update(['principal_id' => $atkPrincipalId]);
        }

        // 3. Sync all active statuses
        Principal::syncAllActiveStatuses();
    }

    public function down(): void
    {
    }
};
