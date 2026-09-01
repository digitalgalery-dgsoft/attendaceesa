<?php

namespace App\Filament\Resources\AttendanceBaps\Pages;

use App\Filament\Resources\AttendanceBaps\AttendanceBapResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceBaps extends ListRecords
{
    protected static string $resource = AttendanceBapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah BAP Manual'),
        ];
    }
}
