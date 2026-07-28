<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeSchedule extends EditRecord
{
    protected static string $resource = EmployeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
