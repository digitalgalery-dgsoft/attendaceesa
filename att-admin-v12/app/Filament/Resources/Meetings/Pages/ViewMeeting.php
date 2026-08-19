<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewMeeting extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MeetingResource::class;

    protected string $view = 'filament.resources.meetings.pages.view-meeting';

    public function getTitle(): string
    {
        return 'Laporan Hasil Meeting: ' . ($this->record->title ?? 'Meeting');
    }

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load([
            'company',
            'workLocation',
            'creator',
            'participants.employee.position',
            'participants.employee.department',
            'attendances.employee'
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(MeetingResource::getUrl('index')),

            EditAction::make()
                ->label('Edit Jadwal Meeting')
                ->color('primary')
                ->icon('heroicon-o-pencil-square')
                ->record($this->record),
        ];
    }
}
