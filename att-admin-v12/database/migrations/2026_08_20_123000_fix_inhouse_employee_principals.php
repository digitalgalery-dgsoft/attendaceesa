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
        $internalCompanyNames = [
            'PT ARINA MULTI KARYA',
            'PT ALVA KARYA PERKASA',
            'PT ANUGRAH TALENTA BERKARYA',
            'PT ANUGRAH TERPERCAYA KERJA',
            'PT ABADI BERKAT ODELIA',
        ];

        // 1. Ensure a Principal exists for each internal company
        $companyPrincipalMap = [];
        $allCompanies = Company::all();
        foreach ($allCompanies as $comp) {
            $prin = Principal::firstOrCreate(
                ['name' => $comp->name],
                [
                    'code' => 'PRIN-' . ($comp->code ?: $comp->id),
                    'company_id' => $comp->id,
                    'is_active' => true,
                ]
            );
            $companyPrincipalMap[$comp->id] = $prin->id;
        }

        // 2. Find Inhouse Department IDs
        $inhouseDeptIds = Department::where('name', 'ilike', '%Inhouse%')->pluck('id')->toArray();

        // 3. Update all Inhouse employees
        foreach ($allCompanies as $comp) {
            $targetPrincipalId = $companyPrincipalMap[$comp->id] ?? null;
            if (!$targetPrincipalId) continue;

            // Update inhouse department employees to their company's principal
            if (!empty($inhouseDeptIds)) {
                Employee::withTrashed()
                    ->where('company_id', $comp->id)
                    ->whereIn('department_id', $inhouseDeptIds)
                    ->update(['principal_id' => $targetPrincipalId]);
            }
        }
    }

    public function down(): void
    {
    }
};
