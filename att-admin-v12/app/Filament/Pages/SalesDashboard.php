<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class SalesDashboard extends BaseDashboard
{
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationLabel(): string
    {
        return 'Sales Dashboard';
    }
    
    public function getTitle(): string
    {
        return 'Sales Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Marketing';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
    
    // Customize route
    protected static string $routePath = '/sales-dashboard';
    protected static ?string $slug = 'sales-dashboard';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Allow super admin maybe
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        $employee = $user->employee;
        if (!$employee) {
            return false;
        }

        // hak akses dashboard berdasarkan supervisor dari karyawan dengan departemen yang memiliki fitur sales report.
        // Check if there are employees where supervisor_id = $employee->id AND their department has_sales_reporting = true
        $isSupervisorOfSales = Employee::where('supervisor_id', $employee->id)
            ->whereHas('department', function ($query) {
                $query->where('has_sales_reporting', true);
            })->exists();
            
        return $isSupervisorOfSales;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\SalesStatsWidget::class,
            \App\Filament\Widgets\SalesPipelineChart::class,
        ];
    }
}
