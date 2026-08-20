<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OdooSyncLog;
use App\Services\OdooSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class OdooSyncReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $title = 'Laporan Sinkronisasi Odoo';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Laporan Odoo Sync';
    protected string $view = 'filament.pages.odoo-sync-report';

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('manage_settings') || auth()->user()->can('view_odoo_sync'));
    }

    public ?string $filterDate = null;
    public ?string $selectedBatchId = 'all_today';
    public ?int $selectedCompanyId = null;

    public ?array $activeLogDetail = null;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->filterDate = Carbon::today('Asia/Jakarta')->toDateString();
        $this->selectedBatchId = 'all_today';
        $this->selectedCompanyId = null;
    }

    public function setToday(): void
    {
        $this->filterDate = Carbon::today('Asia/Jakarta')->toDateString();
        $this->selectedBatchId = 'all_today';
    }

    public function updatedFilterDate(): void
    {
        $this->selectedBatchId = 'all_today';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_now')
                ->label('Sync Semua Company Sekarang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Jalankan Sinkronisasi Semua Company')
                ->modalDescription('Proses sinkronisasi 23.000+ data karyawan akan berjalan di background server secara aman agar tidak terkena timeout browser. Halaman akan otomatis terupdate saat proses berjalan.')
                ->action(function () {
                    try {
                        $artisan = base_path('artisan');
                        $phpBinary = file_exists('/www/server/php/83/bin/php') ? '/www/server/php/83/bin/php' : (PHP_BINARY ?: 'php');
                        
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            @pclose(@popen("start /B \"\" \"{$phpBinary}\" \"{$artisan}\" odoo:sync --trigger=manual", "r"));
                        } else {
                            @exec("nohup {$phpBinary} {$artisan} odoo:sync --trigger=manual > /dev/null 2>&1 &");
                        }

                        $this->filterDate = Carbon::today('Asia/Jakarta')->toDateString();

                        Notification::make()
                            ->title('Sinkronisasi Dimulai di Background')
                            ->body('Proses sinkronisasi sedang berjalan di server. Data & log akan otomatis muncul di tabel dalam 1–2 menit.')
                            ->info()
                            ->duration(10000)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Memulai Sinkronisasi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Get summary metrics for the selected date / batch.
     */
    public function getReportData(): array
    {
        @ini_set('memory_limit', '512M');
        $targetDate = $this->filterDate ?: Carbon::today('Asia/Jakarta')->toDateString();

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // Query logs for the selected date
        $logsQuery = OdooSyncLog::with('company')
            ->whereDate('created_at', $targetDate);

        if ($this->selectedBatchId && $this->selectedBatchId !== 'all_today' && $this->selectedBatchId !== 'latest') {
            $logsQuery->where('batch_id', $this->selectedBatchId);
        }

        if ($this->selectedCompanyId) {
            $logsQuery->where('company_id', $this->selectedCompanyId);
        }

        $logs = $logsQuery->orderBy('created_at', 'desc')->get();

        // Group logs by company for aggregation
        $logsByCompany = $logs->groupBy('company_id');

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
            $companyLogs = $logsByCompany->get($company->id, collect());
            $latestCompanyLog = $companyLogs->first();

            // Aggregation across the day for this company
            $newCount = (int) $companyLogs->sum('new_count');
            $updateCount = (int) ($latestCompanyLog ? $latestCompanyLog->update_count : $companyLogs->sum('update_count'));
            $resignCount = (int) $companyLogs->sum('resign_count');

            // Always get live count of active employees in database
            $totalActive = Employee::where('company_id', $company->id)->where('is_active', true)->count();

            // Collect details if available
            $newEmpDetails = [];
            $updEmpDetails = [];
            $rsgEmpDetails = [];
            foreach ($companyLogs as $cl) {
                if (!empty($cl->details['new_employees'])) {
                    $newEmpDetails = array_merge($newEmpDetails, (array)$cl->details['new_employees']);
                }
                if (!empty($cl->details['updated_employees'])) {
                    $updEmpDetails = array_merge($updEmpDetails, (array)$cl->details['updated_employees']);
                }
                if (!empty($cl->details['resigned_employees'])) {
                    $rsgEmpDetails = array_merge($rsgEmpDetails, (array)$cl->details['resigned_employees']);
                }
            }

            $newData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $newCount,
                'details' => $newEmpDetails,
            ];

            $updateData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $updateCount,
                'details' => $updEmpDetails,
            ];

            $resignData[] = [
                'company_id' => $company->id,
                'code' => $code,
                'name' => $company->name,
                'count' => $resignCount,
                'details' => $rsgEmpDetails,
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
            'target_date' => $targetDate,
            'target_date_formatted' => Carbon::parse($targetDate)->translatedFormat('d F Y'),
            'batch_id' => $this->selectedBatchId,
            'logs_count' => $logs->count(),
            'executed_at' => $logs->first() ? $logs->first()->created_at->format('d M Y H:i:s') : null,
            'trigger_type' => $logs->first() ? $logs->first()->trigger_type : null,
        ];
    }

    /**
     * Get recent sync batches for dropdown filter on the selected date.
     */
    public function getRecentBatches(): array
    {
        $targetDate = $this->filterDate ?: Carbon::today('Asia/Jakarta')->toDateString();

        return OdooSyncLog::whereDate('created_at', $targetDate)
            ->select('batch_id', 'created_at', 'trigger_type', 'status')
            ->selectRaw('SUM(new_count) as total_new, SUM(update_count) as total_update, SUM(resign_count) as total_resign')
            ->groupBy('batch_id', 'created_at', 'trigger_type', 'status')
            ->orderBy('created_at', 'desc')
            ->get()
            ->mapWithKeys(function ($row) {
                $time = $row->created_at->format('H:i:s');
                $label = "Batch {$time} [{$row->trigger_type}] — New: {$row->total_new}, Upd: {$row->total_update}, Resign: {$row->total_resign}";
                return [$row->batch_id => $label];
            })
            ->toArray();
    }

    /**
     * Get history log list for the bottom table strictly filtered for the selected day.
     */
    public function getHistoryLogs()
    {
        $targetDate = $this->filterDate ?: Carbon::today('Asia/Jakarta')->toDateString();

        $query = OdooSyncLog::with('company')
            ->whereDate('created_at', $targetDate)
            ->orderBy('created_at', 'desc');

        if ($this->selectedBatchId && $this->selectedBatchId !== 'all_today' && $this->selectedBatchId !== 'latest') {
            $query->where('batch_id', $this->selectedBatchId);
        }

        if ($this->selectedCompanyId) {
            $query->where('company_id', $this->selectedCompanyId);
        }

        return $query->get();
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
