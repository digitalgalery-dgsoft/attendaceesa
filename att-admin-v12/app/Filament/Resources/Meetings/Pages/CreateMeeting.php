<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
