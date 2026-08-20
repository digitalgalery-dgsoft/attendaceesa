<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Exports\VisitScheduleTemplateExport;
use App\Filament\Resources\Itineraries\ItineraryResource;
use App\Imports\VisitScheduleImport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Itinerary;
use App\Models\Principal;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListItineraries extends Page
{
    protected static string $resource = ItineraryResource::class;
    protected string $view = 'filament.pages.visit-schedule-calendar';

    public int $month;
    public int $year;
    public ?string $branch_id = null;
    public ?string $principal_id = null;
    public ?string $employee_id = null;
    public ?string $search = '';

    public bool $showDetailModal = false;
    public ?array $selectedItinerary = null;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        @ini_set('memory_limit', '512M');
        $this->month = (int)date('n');
        $this->year = (int)date('Y');
        $this->branch_id = null;
        $this->principal_id = null;
        $this->employee_id = null;
        $this->search = '';
    }

    public function prevMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = (int)$date->month;
        $this->year = (int)$date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = (int)$date->month;
        $this->year = (int)$date->year;
    }

    public function today(): void
    {
        $this->month = (int)date('n');
        $this->year = (int)date('Y');
    }

    public function openAddModal(string $dateString): void
    {
        $this->redirect(ItineraryResource::getUrl('create') . '?date=' . $dateString);
    }

    public function openDetailModal(int $itineraryId): void
    {
        $itinerary = Itinerary::with([
            'employee.position',
            'employee.branch',
            'employee.principal',
            'employee.company',
            'items.workLocation',
            'items.principal',
        ])->find($itineraryId);

        if (!$itinerary) {
            Notification::make()->title('Jadwal visit tidak ditemukan')->danger()->send();
            return;
        }

        $emp = $itinerary->employee;
        $itemsData = [];

        foreach ($itinerary->items->sortBy('sequence') as $item) {
            $itemsData[] = [
                'id' => $item->id,
                'sequence' => $item->sequence,
                'location_name' => $item->workLocation?->name ?? 'Unknown Location',
                'location_address' => $item->workLocation?->address ?? '',
                'principal_name' => $item->principal?->name ?? ($emp?->principal?->name ?? ''),
                'visit_type' => $item->visit_type ?? 'store',
                'is_checkin_location' => (bool)$item->is_checkin_location,
                'notes' => $item->notes ?? '',
            ];
        }

        $this->selectedItinerary = [
            'id' => $itinerary->id,
            'date' => $itinerary->date,
            'status' => $itinerary->status,
            'notes' => $itinerary->notes,
            'employee_name' => $emp?->full_name ?? 'Unknown Employee',
            'employee_no' => $emp?->employee_no ?? '-',
            'position' => $emp?->position?->name ?? 'Staff',
            'area' => $emp?->branch?->name ?? ($emp?->branch?->code ?? '-'),
            'principal' => $emp?->principal?->name ?? ($emp?->company?->name ?? '-'),
            'items' => $itemsData,
        ];

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedItinerary = null;
    }

    public function deleteItinerary(int $itineraryId): void
    {
        $itinerary = Itinerary::find($itineraryId);
        if ($itinerary) {
            $itinerary->items()->delete();
            $itinerary->delete();
            Notification::make()->title('Jadwal visit berhasil dihapus')->success()->send();
        }

        $this->closeDetailModal();
    }

    public function getBranchOptionsProperty(): array
    {
        $query = Branch::orderBy('name');
        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
            $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
        }
        return $query->pluck('name', 'id')->toArray();
    }

    public function getPrincipalOptionsProperty(): array
    {
        $query = Principal::orderBy('name');
        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
            $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
        }
        return $query->pluck('name', 'id')->toArray();
    }

    public function getEmployeeOptionsProperty(): array
    {
        $query = Employee::where('is_active', 1)->with(['position', 'branch']);
        if (auth()->check()) {
            $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
        }
        return $query->orderBy('full_name')->get()->mapWithKeys(function ($emp) {
            $pos = $emp->position?->name ?? 'Staff';
            $area = $emp->branch?->name ?? '-';
            return [$emp->id => "{$emp->full_name} ({$pos} - {$area})"];
        })->toArray();
    }

    public function getTotalSchedulesInMonthProperty(): int
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();

        $query = Itinerary::whereBetween('date', [$startDate, $endDate]);

        if (auth()->check()) {
            $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
        }

        if (!empty($this->branch_id)) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $this->branch_id));
        }

        if (!empty($this->principal_id)) {
            $query->whereHas('employee', fn($q) => $q->where('principal_id', $this->principal_id));
        }

        if (!empty($this->employee_id)) {
            $query->where('employee_id', $this->employee_id);
        }

        return $query->count();
    }

    public function getCalendarDaysProperty(): array
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $firstOfMonth->daysInMonth;
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        // Cari tanggal awal grid (Senin terdekat)
        $startOfGrid = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        // Cari tanggal akhir grid (Minggu terdekat setelah akhir bulan)
        $lastOfMonth = Carbon::create($this->year, $this->month, $daysInMonth);
        $endOfGrid = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Preload seluruh itineraries dalam rentang kalender grid
        $itineraryQuery = Itinerary::whereBetween('date', [$startOfGrid->toDateString(), $endOfGrid->toDateString()])
            ->with([
                'employee.position',
                'employee.branch',
                'employee.principal',
                'items.workLocation'
            ]);

        if (auth()->check()) {
            $itineraryQuery = \App\Traits\ScopesUserData::applyUserAccessScope($itineraryQuery);
        }

        if (!empty($this->branch_id)) {
            $itineraryQuery->whereHas('employee', fn($q) => $q->where('branch_id', $this->branch_id));
        }

        if (!empty($this->principal_id)) {
            $itineraryQuery->whereHas('employee', fn($q) => $q->where('principal_id', $this->principal_id));
        }

        if (!empty($this->employee_id)) {
            $itineraryQuery->where('employee_id', $this->employee_id);
        }

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $itineraryQuery->where(function ($q) use ($term) {
                $q->whereHas('employee', function ($empQ) use ($term) {
                    $empQ->where('full_name', 'ilike', $term)
                         ->orWhere('employee_no', 'ilike', $term);
                })->orWhereHas('items.workLocation', function ($locQ) use ($term) {
                    $locQ->where('name', 'ilike', $term);
                });
            });
        }

        $itineraries = $itineraryQuery->get()->groupBy('date');

        $calendarDays = [];
        $current = $startOfGrid->copy();

        while ($current->lte($endOfGrid)) {
            $dateStr = $current->toDateString();
            $dayItineraries = $itineraries->get($dateStr, collect());

            $formattedSchedules = [];
            foreach ($dayItineraries as $it) {
                $emp = $it->employee;
                if (!$emp) continue;

                $pos = $emp->position?->name ?? 'Staff';
                $area = $emp->branch?->name ?? ($emp->branch?->code ?? '-');
                $hasCheckin = $it->items->contains('is_checkin_location', true);

                $formattedSchedules[] = [
                    'id' => $it->id,
                    'employee_name' => $emp->full_name,
                    'position' => $pos,
                    'area' => $area,
                    'status' => $it->status ?? 'approved',
                    'location_count' => $it->items->count(),
                    'has_checkin' => $hasCheckin,
                ];
            }

            $calendarDays[] = [
                'day_number' => $current->day,
                'date_string' => $dateStr,
                'is_current_month' => $current->month === $this->month,
                'is_today' => $dateStr === $todayStr,
                'is_weekend' => in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]),
                'schedules' => $formattedSchedules,
            ];

            $current->addDay();
        }

        return $calendarDays;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Visit Schedule')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(ItineraryResource::getUrl('create')),

            Action::make('import_schedule')
                ->label('Import Visit Schedule (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Jadwal Visit via Excel')
                ->modalDescription('Unggah file Excel jadwal visit karyawan. Gunakan template resmi yang telah disediakan.')
                ->form([
                    FileUpload::make('file')
                        ->label('Pilih File Excel (.xlsx / .xls)')
                        ->disk('local')
                        ->directory('temp-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'application/octet-stream',
                        ])
                        ->required()
                        ->helperText('Pastikan format kolom sesuai dengan template Excel.'),
                ])
                ->action(function (array $data) {
                    try {
                        $filePath = Storage::disk('local')->path($data['file']);

                        $import = new VisitScheduleImport();
                        Excel::import($import, $filePath);

                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }

                        $msg = "Berhasil mengimpor {$import->importedCount} data jadwal visit.";
                        if ($import->skippedCount > 0) {
                            $msg .= " ({$import->skippedCount} baris dilewati).";
                        }

                        if (!empty($import->errors)) {
                            $errorSummary = implode("<br>", array_slice($import->errors, 0, 5));
                            if (count($import->errors) > 5) {
                                $errorSummary .= "<br>...dan " . (count($import->errors) - 5) . " kendala lainnya.";
                            }
                            Notification::make()
                                ->title('Import Selesai dengan Catatan')
                                ->warning()
                                ->body($msg . '<br><br><strong>Detail:</strong><br>' . $errorSummary)
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Berhasil')
                                ->success()
                                ->body($msg)
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Import Jadwal Visit')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('download_template')
                ->label('Download Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new VisitScheduleTemplateExport(), 'Template_Import_Visit_Schedule.xlsx');
                }),
        ];
    }
}
