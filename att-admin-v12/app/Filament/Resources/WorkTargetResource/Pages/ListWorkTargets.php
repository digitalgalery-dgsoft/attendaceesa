<?php

namespace App\Filament\Resources\WorkTargetResource\Pages;

use App\Filament\Resources\WorkTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WorkTargetImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ListWorkTargets extends ListRecords
{
    protected static string $resource = WorkTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->form([
                    FileUpload::make('attachment')
                        ->label('File Excel')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]),
                ])
                ->action(function (array $data) {
                    $file = Storage::disk('public')->path($data['attachment']);
                    
                    try {
                        Excel::import(new WorkTargetImport, $file);
                        Notification::make()
                            ->title('Berhasil import data Target HK')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal import data')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
