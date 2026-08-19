<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OdooSyncLog;
use App\Services\OdooSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class OdooSyncReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $title = 'Laporan Sinkronisasi Odoo';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Laporan Odoo Sync';
    protected string $view = 'filament.pages.odoo-sync-report';

    public ?string $selectedBatchId = 'latest';
    public ?string $filterDate = null;
    public ?int $selectedCompanyId = null;

    public ?array $activeLogDetail = null;

    public function mount(): void
    {
        $this->filterDate = Carbon::today()->format('Y-m-d');
        $latestBatch = OdooSyncLog::latest()->value('batch_id');
        $this->selectedBatchId = $latestBatch ?: 'latest';
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('sync_now')
                ->label('Sync Semua Company Sekarang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Jalankan Sinkronisasi Otomatis Sekarang')
                ->modalDescription('Sistem akan mengeksekusi sinkronisasi seluruh company yang memiliki konfigurasi Odoo secara berurutan.')
                ->action(function () {
                    try {
                        $results = OdooSyncService::syncAllConfiguredCompanies('manual');
                        $this->selectedBatchId = $results['batch_id'];

                        Notification::make()
                            ->title('Sinkronisasi Selesai')
                            ->body("Berhasil sinkronisasi {$results['companies_count']} company. Baru: {$results['total_created']} | Update: {$results['total_updated']} | Resign: {$results['total_resigned']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Get summary metrics for the selected batch/date.
     */
    public function getReportData(): array
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // Get logs based on selected batch
        $logsQuery = OdooSyncLog::with('company');
        if ($this->selectedBatchId && $this->selectedBatchId !== 'latest') {
            $logsQuery->where('batch_id', $this->selectedBatchId);
        } else {
            $latestBatch = OdooSyncLog::latest()->value('batch_id');
            if ($latestBatch) {
                $logsQuery->where('batch_id', $latestBatch);
            }
        }

        if ($this->selectedCompanyId) {
            $logsQuery->where('company_id', $this->selectedCompanyId);
        }

        $logs = $logsQuery->get();
        $logsByCompany = $logs->keyBy('company_id');

        $newData = [];
        $updateData = [];
        $resignData = [];
        $totalEmployeeData = [];

        $sumNew = 0;
        $sumUpdate = 0;
        $sumResign = 0;
        $sumTotalEmployees = 0;

        foreach ($companies as $company) {
            $code = $company->code ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name), 0, 4));
            $log = $logsByCompany->get($company->id);

            $newCount = $log ? $log->new_count : 0;
            $updateCount = $log ? $log->update_count : 0;
            $resignCount = $log ? $log->resign_count : 0;

            // Live count of active employees in company
            $totalActive = Employee::where('company_id', $company->id)->where('is_active', true)->count();
            if ($log && $log->total_employee_count > 0) {
                $totalActive = $log->total_employee_count;
            }

            $newData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $newCount,
                'details' => $log ? ($log->details['new_employees'] ?? []) : [],
            ];

            $updateData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $updateCount,
                'details' => $log ? ($log->details['updated_employees'] ?? []) : [],
            ];

            $resignData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $resignCount,
                'details' => $log ? ($log->details['resigned_employees'] ?? []) : [],
            ];

            $totalEmployeeData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $totalActive,
            ];

            $sumNew += $newCount;
            $sumUpdate += $updateCount;
            $sumResign += $resignCount;
            $sumTotalEmployees += $totalActive;
        }

        return [
            'new_data' => $newData,
            'update_data' => $updateData,
            'resign_data' => $resignData,
            'total_employee_data' => $totalEmployeeData,
            'sum_new' => $sumNew,
            'sum_update' => $sumUpdate,
            'sum_resign' => $sumResign,
            'sum_total_employees' => $sumTotalEmployees,
            'batch_id' => $this->selectedBatchId,
            'logs_count' => $logs->count(),
            'executed_at' => $logs->first() ? $logs->first()->created_at->format('d M Y H:i:s') : null,
            'trigger_type' => $logs->first() ? $logs->first()->trigger_type : null,
        ];
    }

    /**
     * Get recent sync batches for dropdown filter.
     */
    public function getRecentBatches(): array
    {
        return OdooSyncLog::select('batch_id', 'created_at', 'trigger_type', 'status')
            ->selectRaw('SUM(new_count) as total_new, SUM(update_count) as total_update, SUM(resign_count) as total_resign')
            ->groupBy('batch_id', 'created_at', 'trigger_type', 'status')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->mapWithKeys(function ($row) {
                $date = $row->created_at->format('d/m/Y H:i');
                $label = "Batch {$date} [{$row->trigger_type}] — New: {$row->total_new}, Upd: {$row->total_update}, Resign: {$row->total_resign}";
                return [$row->batch_id => $label];
            })
            ->toArray();
    }

    /**
     * Get history log list for the bottom table.
     */
    public function getHistoryLogs()
    {
        return OdooSyncLog::with('company')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * View detail of a specific log modal.
     */
    public function showLogDetail(int $logId): void
    {
        $log = OdooSyncLog::with('company')->find($logId);
        if ($log) {
            $this->activeLogDetail = $log->toArray();
        }
    }

    public function closeLogDetail(): void
    {
        $this->activeLogDetail = null;
    }
}
