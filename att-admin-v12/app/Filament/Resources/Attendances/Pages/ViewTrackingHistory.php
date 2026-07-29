<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewTrackingHistory extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AttendanceResource::class;

    protected string $view = 'filament.resources.attendances.pages.view-tracking-history';

    public $trackingHistories = [];
    public $employeeName = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->employeeName = $this->record->employee->full_name ?? 'Unknown';
        $this->trackingHistories = \App\Models\TrackingHistory::where('attendance_id', $this->record->id)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at'])
            ->toArray();
    }
}
