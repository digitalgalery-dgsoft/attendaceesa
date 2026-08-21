<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations to bind departments to principals instead of companies,
     * populate missing principal_id on all departments, and re-link employees' departments.
     */
    public function up(): void
    {
        @ini_set('memory_limit', '512M');

        // 1. Tambah kolom principal_id ke tabel departments jika belum ada
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'principal_id')) {
                $table->foreignId('principal_id')->nullable()->after('company_id')->constrained('principals')->nullOnDelete();
            }
            if (Schema::hasColumn('departments', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->change();
            }
        });

        // 2. Perbaiki dan lengkapi data principal_id pada setiap department
        $departments = DB::table('departments')->get();

        foreach ($departments as $dept) {
            if (empty($dept->principal_id)) {
                $employeePrincipalId = DB::table('employees')
                    ->where('department_id', $dept->id)
                    ->whereNotNull('principal_id')
                    ->value('principal_id');

                if ($employeePrincipalId) {
                    DB::table('departments')->where('id', $dept->id)->update([
                        'principal_id' => $employeePrincipalId,
                    ]);
                } elseif (!empty($dept->company_id)) {
                    $principalId = DB::table('principals')->where('company_id', $dept->company_id)->value('id');
                    if ($principalId) {
                        DB::table('departments')->where('id', $dept->id)->update([
                            'principal_id' => $principalId,
                        ]);
                    }
                }
            }
        }

        // 3. Cache department mapping: "deptName_principalId" => deptId
        $deptCache = [];
        $allDepts = DB::table('departments')->select('id', 'name', 'principal_id')->get();
        foreach ($allDepts as $d) {
            if ($d->principal_id) {
                $key = strtolower(trim($d->name)) . '_' . $d->principal_id;
                $deptCache[$key] = $d->id;
            }
        }

        // 4. Cache principal to company mapping
        $principalCompanyMap = DB::table('principals')->pluck('company_id', 'id')->toArray();
        $deptNameMap = DB::table('departments')->pluck('name', 'id')->toArray();

        // 5. Update employees in memory-efficient chunks
        DB::table('employees')
            ->whereNotNull('principal_id')
            ->select('id', 'principal_id', 'department_id', 'company_id')
            ->chunkById(300, function ($employees) use (&$deptCache, $principalCompanyMap, $deptNameMap) {
                foreach ($employees as $emp) {
                    $principalId = $emp->principal_id;
                    $companyId   = $emp->company_id ?: ($principalCompanyMap[$principalId] ?? null);

                    $currentDeptName = $emp->department_id && isset($deptNameMap[$emp->department_id]) 
                        ? trim($deptNameMap[$emp->department_id]) 
                        : 'Inhouse';

                    $cacheKey = strtolower($currentDeptName) . '_' . $principalId;

                    if (isset($deptCache[$cacheKey])) {
                        $targetDeptId = $deptCache[$cacheKey];
                    } else {
                        $code = 'DEP-' . strtoupper(Str::random(5));
                        $targetDeptId = DB::table('departments')->insertGetId([
                            'principal_id'        => $principalId,
                            'company_id'          => $companyId,
                            'name'                => $currentDeptName,
                            'code'                => $code,
                            'is_active'           => true,
                            'has_sales_reporting' => (strtoupper($currentDeptName) === 'SALES'),
                            'working_days'        => json_encode(['1', '2', '3', '4', '5']),
                            'cutoff_start_date'   => 26,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                        $deptCache[$cacheKey] = $targetDeptId;
                    }

                    if ($emp->department_id !== $targetDeptId) {
                        DB::table('employees')->where('id', $emp->id)->update([
                            'department_id' => $targetDeptId,
                        ]);
                    }
                }
            });

        // 6. Bersihkan department yatim yang tidak punya principal_id dan tidak punya karyawan
        $orphanDepts = DB::table('departments')->whereNull('principal_id')->get();
        foreach ($orphanDepts as $orphan) {
            $hasEmployees = DB::table('employees')->where('department_id', $orphan->id)->exists();
            if (!$hasEmployees) {
                DB::table('departments')->where('id', $orphan->id)->delete();
            } else {
                $firstPrincipalId = DB::table('principals')->value('id');
                if ($firstPrincipalId) {
                    DB::table('departments')->where('id', $orphan->id)->update(['principal_id' => $firstPrincipalId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'principal_id')) {
                $table->dropForeign(['principal_id']);
                $table->dropColumn('principal_id');
            }
        });
    }
};
