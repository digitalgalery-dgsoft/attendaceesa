<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Carbon\Carbon;

class ViewTrackingHistory extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AttendanceResource::class;

    protected string $view = 'filament.resources.attendances.pages.view-tracking-history';

    public $trackingHistories = [];
    public $employeeName = '';
    public $attendanceDate = '';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('clearTrackingHistory')
                ->label('Clear Tracking')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function () {
                    $date = \Carbon\Carbon::parse($this->record->attendance_date)->format('Y-m-d');
                    \App\Models\TrackingHistory::where('employee_id', $this->record->employee_id)
                        ->whereDate('created_at', $date)
                        ->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Tracking history cleared')
                        ->success()
                        ->send();
                        
                    return redirect(request()->header('Referer'));
                }),
        ];
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->employeeName = $this->record->employee->full_name ?? 'Unknown';
        $this->attendanceDate = $this->record->attendance_date;

        $date = Carbon::parse($this->record->attendance_date)->format('Y-m-d');

        // FIX: Query berdasarkan employee_id + tanggal absensi, BUKAN hanya attendance_id
        // Sebelumnya hanya filter by attendance_id yang bisa null jika tracking dimulai
        // sebelum check-in tercatat, atau ada edge case lain yang menyebabkan data tidak muncul.
        // Sekarang kita ambil semua titik tracking untuk karyawan ini pada tanggal yang sama.
        $employee = $this->record->employee;
        $employeeSchedule = $this->record->employeeSchedule;
        
        $timezone = 'Asia/Jakarta';
        if ($employeeSchedule && $employeeSchedule->workLocation && $employeeSchedule->workLocation->timezone) {
            $timezone = $employeeSchedule->workLocation->timezone;
        } elseif ($employee && $employee->timezone && in_array($employee->timezone, ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])) {
            $timezone = $employee->timezone;
        } elseif ($employee && $employee->company && $employee->company->timezone) {
            $timezone = $employee->company->timezone;
        }

        try {
            new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            $timezone = 'Asia/Jakarta';
        }

        $this->trackingHistories = \App\Models\TrackingHistory::where('employee_id', $this->record->employee_id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at'])
            ->map(function ($item) use ($timezone) {
                // created_at disimpan sebagai UTC di DB (sejak perbaikan store() API).
                // Carbon::parse dengan 'UTC' lalu setTimezone ke lokal.
                $time = \Carbon\Carbon::parse($item->created_at, 'UTC')->setTimezone($timezone);

                return [
                    'latitude'   => (float) $item->latitude,
                    'longitude'  => (float) $item->longitude,
                    'created_at' => $time->format('H:i:s'),
                ];
            })
            ->toArray();
    }
}
