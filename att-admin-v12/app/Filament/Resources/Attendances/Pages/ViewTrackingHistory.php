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
    public $employee = null;
    public $schedule = null;
    public $activityLogs = [];
    public $totalDistanceMeter = 0;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('clearTrackingHistory')
                ->label('Hapus Riwayat Tracking')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Riwayat GPS Tracking?')
                ->modalDescription('Apakah Anda yakin ingin menghapus semua data riwayat tracking GPS untuk tanggal ini?')
                ->action(function () {
                    $date = \Carbon\Carbon::parse($this->record->attendance_date)->format('Y-m-d');
                    \App\Models\TrackingHistory::where('employee_id', $this->record->employee_id)
                        ->whereDate('created_at', $date)
                        ->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Riwayat tracking GPS berhasil dibersihkan')
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

        $this->employee = $this->record->employee;
        if ($this->employee) {
            $this->employee->load(['company', 'principal', 'branch', 'department', 'position']);
        }

        $this->schedule = \App\Models\EmployeeSchedule::where('employee_id', $this->record->employee_id)
            ->where('schedule_date', $date)
            ->with(['workLocation.company', 'shift'])
            ->first();

        $this->activityLogs = \App\Models\AttendanceLog::where('attendance_id', $this->record->id)
            ->orWhere(function($q) use ($date) {
                $q->where('employee_id', $this->record->employee_id)
                  ->whereDate('logged_at', $date);
            })
            ->with(['itineraryItem.workLocation'])
            ->orderBy('logged_at', 'asc')
            ->get();
        
        $timezone = 'Asia/Jakarta';
        if ($this->schedule && $this->schedule->workLocation && $this->schedule->workLocation->timezone) {
            $timezone = $this->schedule->workLocation->timezone;
        } elseif ($this->employee && $this->employee->timezone && in_array($this->employee->timezone, ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])) {
            $timezone = $this->employee->timezone;
        } elseif ($this->employee && $this->employee->company && $this->employee->company->timezone) {
            $timezone = $this->employee->company->timezone;
        }

        try {
            new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            $timezone = 'Asia/Jakarta';
        }

        $points = \App\Models\TrackingHistory::where('employee_id', $this->record->employee_id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at']);

        $this->totalDistanceMeter = 0;
        $prevLat = null;
        $prevLng = null;

        $this->trackingHistories = $points->map(function ($item) use ($timezone, &$prevLat, &$prevLng) {
            $lat = (float) $item->latitude;
            $lng = (float) $item->longitude;

            if ($prevLat !== null && $prevLng !== null) {
                // Haversine formula
                $dLat = deg2rad($lat - $prevLat);
                $dLon = deg2rad($lng - $prevLng);
                $a = sin($dLat / 2) * sin($dLat / 2) +
                     cos(deg2rad($prevLat)) * cos(deg2rad($lat)) *
                     sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $this->totalDistanceMeter += (6371000 * $c);
            }
            $prevLat = $lat;
            $prevLng = $lng;

            $time = \Carbon\Carbon::parse($item->created_at, 'UTC')->setTimezone($timezone);

            return [
                'latitude'   => $lat,
                'longitude'  => $lng,
                'created_at' => $time->format('H:i:s'),
            ];
        })->toArray();
    }
}
