<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait ScopesUserData
{
    /**
     * Apply User branch/area and principal access scoping to an Eloquent builder.
     */
    public static function applyUserAccessScope(Builder $query, ?User $user = null, ?string $branchColumn = null, ?string $principalColumn = null, string $employeeRelation = 'employee'): Builder
    {
        $user = $user ?: auth()->user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $branchIds = $user->getAccessibleBranchIds();
        $principalIds = $user->getAccessiblePrincipalIds();

        // 1. Branch / Area Scoping
        if (!empty($branchIds)) {
            $tableName = $query->getModel()->getTable();
            
            // Check if model itself is Branch
            if ($tableName === 'branches') {
                $query->whereIn('branches.id', $branchIds);
            } elseif ($branchColumn && Schema::hasColumn($tableName, $branchColumn)) {
                $query->whereIn("{$tableName}.{$branchColumn}", $branchIds);
            } elseif (Schema::hasColumn($tableName, 'branch_id')) {
                $query->whereIn("{$tableName}.branch_id", $branchIds);
            } elseif (method_exists($query->getModel(), $employeeRelation)) {
                $query->whereHas($employeeRelation, function (Builder $q) use ($branchIds) {
                    $q->whereIn('employees.branch_id', $branchIds);
                });
            }
        }

        // 2. Principal Scoping
        if (!empty($principalIds)) {
            $tableName = $query->getModel()->getTable();

            // Check if model itself is Principal
            if ($tableName === 'principals') {
                $query->whereIn('principals.id', $principalIds);
            } elseif ($principalColumn && Schema::hasColumn($tableName, $principalColumn)) {
                $query->whereIn("{$tableName}.{$principalColumn}", $principalIds);
            } elseif (Schema::hasColumn($tableName, 'principal_id')) {
                $query->whereIn("{$tableName}.principal_id", $principalIds);
            } elseif (method_exists($query->getModel(), $employeeRelation)) {
                $query->whereHas($employeeRelation, function (Builder $q) use ($principalIds) {
                    $q->whereIn('employees.principal_id', $principalIds);
                });
            }
        }

        return $query;
    }
}
