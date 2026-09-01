<?php

namespace App\Filament\Resources\AttendanceBaps\Pages;

use App\Filament\Resources\AttendanceBaps\AttendanceBapResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceBap extends ViewRecord
{
    protected static string $resource = AttendanceBapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
