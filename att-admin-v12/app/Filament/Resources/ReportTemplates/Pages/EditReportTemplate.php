<?php

namespace App\Filament\Resources\ReportTemplates\Pages;

use App\Filament\Resources\ReportTemplates\ReportTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReportTemplate extends EditRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncToPeers')
                ->label('Sync ke Server Lain')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sinkronkan Template ke Server ESA Lainnya')
                ->modalDescription('Kirimkan susunan form template yang sedang Anda edit ini ke Server ESA lainnya (AMK, AKP, ATK) agar langsung seragam.')
                ->action(function () {
                    $record = $this->getRecord();
                    $syncService = app(\App\Services\TemplateSyncService::class);
                    $results = $syncService->syncToPeers($record);

                    if (empty($results)) {
                        Notification::make()
                            ->title('Tidak Ada Server Tujuan Lain')
                            ->body('Server saat ini adalah satu-satunya endpoint yang terkonfigurasi.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $allSuccess = true;
                    $messages = [];
                    foreach ($results as $res) {
                        $statusIcon = $res['success'] ? '✅' : '❌';
                        $messages[] = "{$statusIcon} {$res['name']}: {$res['message']}";
                        if (!$res['success']) {
                            $allSuccess = false;
                        }
                    }

                    Notification::make()
                        ->title($allSuccess ? 'Sinkronisasi Berhasil!' : 'Hasil Sinkronisasi')
                        ->body(implode("\n", $messages))
                        ->status($allSuccess ? 'success' : 'warning')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
