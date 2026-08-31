<?php

namespace App\Filament\Resources\Principals\Pages;

use App\Filament\Resources\Principals\PrincipalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrincipals extends ListRecords
{
    protected static string $resource = PrincipalResource::class;

    public function mount(): void
    {
        parent::mount();
        \App\Models\Principal::syncAllActiveStatuses();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sync_status')
                ->label('Sinkronkan Status Aktif')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    \App\Models\Principal::syncAllActiveStatuses();
                    \Filament\Notifications\Notification::make()
                        ->title('Status Prinsiple Berhasil Disinkronkan')
                        ->body('Prinsiple tanpa karyawan aktif telah dinonaktifkan secara otomatis.')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\PrincipalImporter::class),
            CreateAction::make(),
        ];
    }
}
