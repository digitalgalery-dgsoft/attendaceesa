<?php

namespace App\Filament\Pages;

use App\Exports\TurnOverExport;
use App\Models\Company;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TurnOverReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Turnover Report';
    protected static ?string $title = 'Turn Over Report';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.turn-over-report';

    public ?string $year = null;
    public ?string $company_id = null;

    protected ?array $memoizedData = null;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function boot(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->year = date('Y');
        $this->company_id = null;
        $this->form->fill([
            'year' => $this->year,
            'company_id' => null,
        ]);
    }

    public function rendering(): void
    {
        @ini_set('memory_limit', '512M');
    }

    public function form(Schema $form): Schema
    {
        $years = [];
        for ($i = (int)date('Y'); $i >= (int)date('Y') - 5; $i--) {
            $years[(string)$i] = (string)$i;
        }

        return $form
            ->schema([
                Grid::make(2)->schema([
                    Select::make('year')
                        ->label('Tahun')
                        ->options($years)
                        ->default((string)date('Y'))
                        ->required()
                        ->live(),
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(Company::orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua Perusahaan')
                        ->live(),
                ])
            ])
            ->statePath('');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->exportExcel()),
            Action::make('refresh')
                ->label('Segarkan Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => Notification::make()->title('Data Turnover Diperbarui')->success()->send()),
        ];
    }

    /**
     * Mengambil data Turnover bulanan (Join, Resign, Net) secara cepat dan aman
     */
    public function getTurnOverData(): array
    {
        if ($this->memoizedData !== null) {
            return $this->memoizedData;
        }

        @ini_set('memory_limit', '512M');

        $year = $this->year ?: date('Y');
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $employees = DB::table('employees')
            ->whereNull('deleted_at')
            ->when(!empty($this->company_id), function ($q) {
                return $q->where('company_id', $this->company_id);
            })
            ->where(function ($q) use ($startDate, $endDate, $year) {
                $q->whereBetween('join_date', [$startDate, $endDate])
                  ->orWhereBetween('resign_date', [$startDate, $endDate])
                  ->orWhere(function ($sq) use ($startDate, $endDate) {
                      $sq->where('employment_status', 'resigned')
                         ->whereBetween('updated_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                         ->whereNull('resign_date');
                  });
            })
            ->select([
                'id',
                'company_id',
                DB::raw("SUBSTRING(CAST(join_date AS VARCHAR), 1, 10) as join_date_str"),
                DB::raw("SUBSTRING(CAST(resign_date AS VARCHAR), 1, 10) as resign_date_str"),
                DB::raw("SUBSTRING(CAST(updated_at AS VARCHAR), 1, 10) as updated_at_str"),
                'employment_status',
            ])
            ->get();

        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $prefix = "{$year}-{$monthStr}";

            $joined = 0;
            $resigned = 0;

            foreach ($employees as $emp) {
                if (!empty($emp->join_date_str) && str_starts_with($emp->join_date_str, $prefix)) {
                    $joined++;
                }

                if (!empty($emp->resign_date_str) && str_starts_with($emp->resign_date_str, $prefix)) {
                    $resigned++;
                } elseif (empty($emp->resign_date_str) && $emp->employment_status === 'resigned' && !empty($emp->updated_at_str) && str_starts_with($emp->updated_at_str, $prefix)) {
                    $resigned++;
                }
            }

            $data[] = [
                'month' => $months[$month - 1],
                'joined' => $joined,
                'resigned' => $resigned,
                'net' => $joined - $resigned,
            ];
        }

        return $this->memoizedData = $data;
    }

    public function exportExcel()
    {
        $rawData = $this->getTurnOverData();
        $exportData = [];

        foreach ($rawData as $row) {
            $exportData[] = [
                $row['month'],
                $row['joined'],
                $row['resigned'],
                $row['net'],
            ];
        }

        return Excel::download(new TurnOverExport($exportData, $this->year ?: date('Y')), 'TurnOver_Report_' . ($this->year ?: date('Y')) . '.xlsx');
    }
}
