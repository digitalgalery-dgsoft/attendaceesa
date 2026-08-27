<?php

namespace App\Filament\Resources\ReportSubmissions\Pages;

use App\Filament\Resources\ReportSubmissions\ReportSubmissionResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewReportSubmission extends ViewRecord
{
    protected static string $resource = ReportSubmissionResource::class;
    protected string $view = 'filament.resources.report-submissions.view';

    public function approveSubmission(): void
    {
        $this->record->update([
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Laporan Berhasil Disetujui')
            ->success()
            ->send();
    }

    public function rejectSubmission(): void
    {
        $this->record->update([
            'status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Laporan Ditolak')
            ->warning()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui Laporan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Laporan Ini?')
                ->modalDescription('Laporan akan ditandai sebagai Terverifikasi (Valid).')
                ->action(fn () => $this->approveSubmission())
                ->visible(fn () => in_array($this->record->status ?? 'pending', ['pending', 'submitted', 'rejected'])),

            Action::make('reject')
                ->label('Tolak Laporan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Laporan Ini?')
                ->modalDescription('Laporan akan ditandai sebagai Ditolak (Tidak Sesuai).')
                ->action(fn () => $this->rejectSubmission())
                ->visible(fn () => in_array($this->record->status ?? 'pending', ['pending', 'submitted', 'approved', 'verified'])),

            Action::make('back')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ReportSubmissionResource::getUrl('index')),
        ];
    }
}
