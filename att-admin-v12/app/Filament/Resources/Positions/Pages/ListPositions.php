<?php

namespace App\Filament\Resources\Positions\Pages;

use App\Filament\Resources\Positions\PositionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('turn_on_all_face')
                ->label('Set Semua Face Recognition ON')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aktifkan Face Recognition untuk Semua Jabatan')
                ->modalDescription('Apakah Anda yakin ingin mengaktifkan kewajiban Face Recognition (Liveness AI) untuk seluruh jabatan?')
                ->action(function () {
                    DB::table('positions')->update(['require_face_recognition' => true]);
                    Notification::make()
                        ->title('Berhasil Diperbarui')
                        ->body('Kewajiban Face Recognition telah diaktifkan untuk seluruh jabatan.')
                        ->success()
                        ->send();
                }),
            Action::make('turn_off_all_face')
                ->label('Set Semua Face Recognition OFF')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Nonaktifkan Face Recognition untuk Semua Jabatan')
                ->modalDescription('Apakah Anda yakin ingin menonaktifkan kewajiban Face Recognition (Liveness AI) untuk seluruh jabatan?')
                ->action(function () {
                    DB::table('positions')->update(['require_face_recognition' => false]);
                    Notification::make()
                        ->title('Berhasil Diperbarui')
                        ->body('Kewajiban Face Recognition telah dinonaktifkan untuk seluruh jabatan.')
                        ->warning()
                        ->send();
                }),
            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\PositionImporter::class),
            CreateAction::make(),
        ];
    }
}
