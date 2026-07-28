<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Textarea;

class ViewLeaveRequest extends ViewRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Approve Head')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan (Opsional)')
                        ->maxLength(255),
                ])
                ->visible(function () {
                    $user = auth()->user();
                    $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                    if ($isAdmin) return $this->record->head_approval_status === 'pending';
                    if (!$user->employee) return false;
                    return $this->record->head_approval_status === 'pending' && $this->record->employee->supervisor_id === $user->employee->id;
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'head_approval_status' => 'approved',
                        'head_approved_by'     => auth()->id(),
                        'head_approved_at'     => now(),
                        'head_approval_notes'  => $data['notes'] ?? null,
                    ]);
                    $body = 'Pengajuan permit Anda telah disetujui oleh Head.';
                    if (!empty($data['notes'])) {
                        $body .= ' Catatan: ' . $data['notes'];
                    }
                    Notification::make()
                        ->title('Permit Disetujui Head')
                        ->body($body)
                        ->success()
                        ->sendToDatabase($this->record->employee);
                }),
            Actions\Action::make('Reject Head')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan (Opsional)')
                        ->maxLength(255),
                ])
                ->visible(function () {
                    $user = auth()->user();
                    $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                    if ($isAdmin) return $this->record->head_approval_status === 'pending';
                    if (!$user->employee) return false;
                    return $this->record->head_approval_status === 'pending' && $this->record->employee->supervisor_id === $user->employee->id;
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'head_approval_status' => 'rejected',
                        'head_approved_by'     => auth()->id(),
                        'head_approved_at'     => now(),
                        'status'               => 'rejected',
                        'head_approval_notes'  => $data['notes'] ?? null,
                    ]);
                    $body = 'Pengajuan permit Anda telah ditolak oleh Head.';
                    if (!empty($data['notes'])) {
                        $body .= ' Catatan: ' . $data['notes'];
                    }
                    Notification::make()
                        ->title('Permit Ditolak Head')
                        ->body($body)
                        ->danger()
                        ->sendToDatabase($this->record->employee);
                }),
            Actions\Action::make('Approve HRD')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan (Opsional)')
                        ->maxLength(255),
                ])
                ->visible(function () {
                    $user = auth()->user();
                    $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                    $isHRD = $user->hasRole(['HRD', 'hrd']) || $isAdmin;
                    return $isHRD && $this->record->hrd_approval_status === 'pending' && ($this->record->head_approval_status === 'approved' || is_null($this->record->employee->supervisor_id));
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'hrd_approval_status' => 'approved',
                        'hrd_approved_by'     => auth()->id(),
                        'hrd_approved_at'     => now(),
                        'status'              => 'approved',
                        'hrd_approval_notes'  => $data['notes'] ?? null,
                    ]);
                    $body = 'Pengajuan permit Anda telah disetujui oleh HRD.';
                    if (!empty($data['notes'])) {
                        $body .= ' Catatan: ' . $data['notes'];
                    }
                    Notification::make()
                        ->title('Permit Disetujui HRD')
                        ->body($body)
                        ->success()
                        ->sendToDatabase($this->record->employee);
                }),
            Actions\Action::make('Reject HRD')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan (Opsional)')
                        ->maxLength(255),
                ])
                ->visible(function () {
                    $user = auth()->user();
                    $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                    $isHRD = $user->hasRole(['HRD', 'hrd']) || $isAdmin;
                    return $isHRD && $this->record->hrd_approval_status === 'pending';
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'hrd_approval_status' => 'rejected',
                        'hrd_approved_by'     => auth()->id(),
                        'hrd_approved_at'     => now(),
                        'status'              => 'rejected',
                        'hrd_approval_notes'  => $data['notes'] ?? null,
                    ]);
                    $body = 'Pengajuan permit Anda telah ditolak oleh HRD.';
                    if (!empty($data['notes'])) {
                        $body .= ' Catatan: ' . $data['notes'];
                    }
                    Notification::make()
                        ->title('Permit Ditolak HRD')
                        ->body($body)
                        ->danger()
                        ->sendToDatabase($this->record->employee);
                }),
            Actions\Action::make('Cetak Surat Cuti')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->visible(function () {
                    return $this->record->status === 'approved' && $this->record->type === 'cuti';
                })
                ->action(function () {
                    $pdf = Pdf::loadView('pdf.surat-cuti', ['record' => $this->record]);
                    $filename = 'Surat-Cuti-' . str_replace(' ', '-', $this->record->employee->full_name) . '-' . date('Ymd', strtotime($this->record->start_date)) . '.pdf';
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),
        ];
    }
}
