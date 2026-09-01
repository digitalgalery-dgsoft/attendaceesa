<?php

namespace App\Filament\Resources\AttendanceBaps\Pages;

use App\Filament\Resources\AttendanceBaps\AttendanceBapResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceBap extends EditRecord
{
    protected static string $resource = AttendanceBapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
