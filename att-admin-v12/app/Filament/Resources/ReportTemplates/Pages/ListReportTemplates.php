<?php

namespace App\Filament\Resources\ReportTemplates\Pages;

use App\Filament\Resources\ReportTemplates\ReportTemplateResource;
use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListReportTemplates extends ListRecords
{
    protected static string $resource = ReportTemplateResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_dulux')
                ->label('Sinkronkan Form Dulux')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    ReportTemplate::syncDuluxMergedStockEnd();
                    Notification::make()
                        ->title('Form Dulux Berhasil Disinkronkan')
                        ->body('Laporan Tinter telah dihapus dan disatukan ke Laporan Stock End dengan 12 field.')
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Buat Template Form Baru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
