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
                // Cari principal dari karyawan yang terhubung ke department ini
                $employeePrincipalId = DB::table('employees')
                    ->where('department_id', $dept->id)
                    ->whereNotNull('principal_id')
                    ->value('principal_id');

                if ($employeePrincipalId) {
                    DB::table('departments')->where('id', $dept->id)->update([
                        'principal_id' => $employeePrincipalId,
                    ]);
                } elseif (!empty($dept->company_id)) {
                    // Cari principal inhouse yang sesuai dengan company_id
                    $principal = DB::table('principals')->where('company_id', $dept->company_id)->first();
                    if ($principal) {
                        DB::table('departments')->where('id', $dept->id)->update([
                            'principal_id' => $principal->id,
                        ]);
                    }
                }
            }
        }

        // 3. Pastikan setiap karyawan memiliki department yang terikat ke principal karyawan tersebut
        $employees = DB::table('employees')->whereNotNull('principal_id')->get();

        // Cache department mapping: "deptName_principalId" => deptId
        $deptCache = [];
        $allDepts = DB::table('departments')->get();
        foreach ($allDepts as $d) {
            if ($d->principal_id) {
                $key = strtolower(trim($d->name)) . '_' . $d->principal_id;
                $deptCache[$key] = $d->id;
            }
        }

        foreach ($employees as $emp) {
            $principalId = $emp->principal_id;
            $principal = DB::table('principals')->where('id', $principalId)->first();
            $companyId = $emp->company_id ?: ($principal ? $principal->company_id : null);

            // Dapatkan nama department yang diinginkan
            $currentDept = $emp->department_id ? DB::table('departments')->where('id', $emp->department_id)->first() : null;
            $deptName = $currentDept ? trim($currentDept->name) : 'Inhouse';

            $cacheKey = strtolower($deptName) . '_' . $principalId;

            if (isset($deptCache[$cacheKey])) {
                $targetDeptId = $deptCache[$cacheKey];
            } else {
                // Buat department baru yang terikat ke principal_id ini
                $code = 'DEP-' . strtoupper(Str::random(5));
                $targetDeptId = DB::table('departments')->insertGetId([
                    'principal_id'        => $principalId,
                    'company_id'          => $companyId,
                    'name'                => $deptName,
                    'code'                => $code,
                    'is_active'           => true,
                    'has_sales_reporting' => ($deptName === 'SALES' || ($currentDept && $currentDept->has_sales_reporting)),
                    'working_days'        => json_encode(['1', '2', '3', '4', '5']),
                    'cutoff_start_date'   => $currentDept ? $currentDept->cutoff_start_date : 26,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
                $deptCache[$cacheKey] = $targetDeptId;
            }

            // Update department_id pada employee jika berbeda
            if ($emp->department_id !== $targetDeptId) {
                DB::table('employees')->where('id', $emp->id)->update([
                    'department_id' => $targetDeptId,
                ]);
            }
        }

        // 4. Bersihkan department yatim yang tidak punya principal_id dan tidak punya karyawan
        $orphanDepts = DB::table('departments')->whereNull('principal_id')->get();
        foreach ($orphanDepts as $orphan) {
            $hasEmployees = DB::table('employees')->where('department_id', $orphan->id)->exists();
            if (!$hasEmployees) {
                DB::table('departments')->where('id', $orphan->id)->delete();
            } else {
                // Berikan fallback principal pertama yang ada jika masih ada employee
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
