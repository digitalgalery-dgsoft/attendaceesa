<?php

namespace App\Filament\Resources\AttendanceBaps\Tables;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\BapRequest;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class AttendanceBapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'  => 'Menunggu Approval',
                        'approved' => 'Disetujui (Approved)',
                        'rejected' => 'Ditolak (Rejected)',
                        default    => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tgl Absen')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BapRequest $record): string => ($record->employee?->employee_no ?? '-') . ' • ' . ($record->employee?->position?->name ?? '-')),

                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('workLocation.name')
                    ->label('Lokasi / Toko')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('checkin_time')
                    ->label('Jam Masuk')
                    ->formatStateUsing(fn ($record) => $record->checkin_time ?: '-')
                    ->badge()
                    ->color('success'),

                TextColumn::make('checkout_time')
                    ->label('Jam Pulang')
                    ->formatStateUsing(fn ($record) => $record->checkout_time ?: '-')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('issue_category')
                    ->label('Kategori Kendala')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'app_error'    => 'danger',
                        'gps_network'  => 'warning',
                        'device_issue' => 'gray',
                        'server_down'  => 'danger',
                        default        => 'info',
                    })
                    ->formatStateUsing(fn ($record) => $record->issue_category_label)
                    ->toggleable(),

                ImageColumn::make('evidence_url')
                    ->label('Bukti Screenshot')
                    ->circular()
                    ->getStateUsing(fn (BapRequest $record) => $record->evidence_url)
                    ->openUrlInNewTab()
                    ->url(fn (BapRequest $record) => $record->evidence_url),

                TextColumn::make('reason')
                    ->label('Alasan / Kendala')
                    ->limit(35)
                    ->tooltip(fn (BapRequest $record): ?string => $record->reason)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Tgl Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Approval')
                    ->options([
                        'pending'  => 'Menunggu Approval (Pending)',
                        'approved' => 'Disetujui (Approved)',
                        'rejected' => 'Ditolak (Rejected)',
                    ]),

                SelectFilter::make('principal_id')
                    ->label('Prinsiple')
                    ->relationship('principal', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('issue_category')
                    ->label('Kategori Kendala')
                    ->options([
                        'app_error'    => 'Kendala Aplikasi',
                        'gps_network'  => 'Kendala Sinyal / GPS',
                        'device_issue' => 'Kendala Handphone',
                        'server_down'  => 'Server Error / Maintenance',
                        'other'        => 'Kendala Lainnya',
                    ]),
            ])
            ->recordActions([
                // ─── ACTION SETUJUI (APPROVE) ──────────────────────────────────
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BapRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Persetujuan BAP (Bukti Absensi Manual)')
                    ->modalDescription(fn (BapRequest $record) => "Menyetujui BAP akan secara otomatis mencatat kehadiran karyawan '{$record->employee?->full_name}' pada tanggal {$record->date->format('d M Y')} sebagai HADIR (Present) dan menghilangkan status Alpha.")
                    ->modalSubmitActionLabel('Ya, Setujui & Catat Presensi')
                    ->form([
                        TextInput::make('checkin_time')
                            ->label('Jam Masuk Disetujui')
                            ->default(fn (BapRequest $record) => $record->checkin_time ?: '08:00')
                            ->required(),

                        TextInput::make('checkout_time')
                            ->label('Jam Pulang Disetujui')
                            ->default(fn (BapRequest $record) => $record->checkout_time ?: '17:00'),

                        Textarea::make('admin_notes')
                            ->label('Catatan Verifikator')
                            ->placeholder('Opsional: Bukti timestamp valid dan diverifikasi...')
                            ->rows(2),
                    ])
                    ->action(function (BapRequest $record, array $data): void {
                        $dateStr = $record->date->format('Y-m-d');
                        $checkinTime = trim($data['checkin_time'] ?: '08:00');
                        $checkoutTime = !empty($data['checkout_time']) ? trim($data['checkout_time']) : null;

                        $checkinDateTime = Carbon::parse("{$dateStr} {$checkinTime}");
                        $checkoutDateTime = $checkoutTime ? Carbon::parse("{$dateStr} {$checkoutTime}") : null;
                        $workDuration = $checkoutDateTime ? $checkoutDateTime->diffInMinutes($checkinDateTime) : 0;

                        // 1. Buat / Update record Attendance
                        $attendance = Attendance::updateOrCreate(
                            [
                                'employee_id'     => $record->employee_id,
                                'attendance_date' => $dateStr,
                            ],
                            [
                                'employee_schedule_id'  => $record->employee_schedule_id,
                                'status'                => 'present',
                                'checkin_at'            => $checkinDateTime->toDateTimeString(),
                                'checkout_at'           => $checkoutDateTime ? $checkoutDateTime->toDateTimeString() : null,
                                'work_duration_minutes' => (int) $workDuration,
                                'late_minutes'          => 0,
                                'early_leave_minutes'   => 0,
                                'overtime_minutes'      => 0,
                                'is_manual_correction'  => true,
                                'correction_note'       => 'Disetujui via BAP (' . $record->issue_category_label . '): ' . ($record->reason ?: 'Bukti manual terverifikasi'),
                            ]
                        );

                        // 2. Buat / Update AttendanceLog untuk Check-in
                        $checkinLog = AttendanceLog::updateOrCreate(
                            [
                                'attendance_id' => $attendance->id,
                                'log_type'      => 'checkin',
                            ],
                            [
                                'employee_id'                  => $record->employee_id,
                                'employee_schedule_id'         => $record->employee_schedule_id,
                                'work_location_id'             => $record->work_location_id,
                                'logged_at'                    => $checkinDateTime->toDateTimeString(),
                                'client_logged_at'             => $checkinDateTime->toDateTimeString(),
                                'photo_path'                   => $record->evidence_path,
                                'source'                       => 'bap_manual',
                                'validation_status'            => 'valid',
                                'is_inside_geofence'           => true,
                                'distance_from_location_meter' => 0,
                                'address_text'                 => 'Disinkronkan via BAP Manual (' . $record->issue_category_label . ')',
                                'note'                         => 'Disetujui via BAP: ' . $record->reason,
                            ]
                        );

                        $attendance->update(['checkin_log_id' => $checkinLog->id]);

                        // 3. Buat / Update AttendanceLog untuk Check-out jika ada
                        if ($checkoutDateTime) {
                            $checkoutLog = AttendanceLog::updateOrCreate(
                                [
                                    'attendance_id' => $attendance->id,
                                    'log_type'      => 'checkout',
                                ],
                                [
                                    'employee_id'                  => $record->employee_id,
                                    'employee_schedule_id'         => $record->employee_schedule_id,
                                    'work_location_id'             => $record->work_location_id,
                                    'logged_at'                    => $checkoutDateTime->toDateTimeString(),
                                    'client_logged_at'             => $checkoutDateTime->toDateTimeString(),
                                    'photo_path'                   => $record->evidence_path,
                                    'source'                       => 'bap_manual',
                                    'validation_status'            => 'valid',
                                    'is_inside_geofence'           => true,
                                    'distance_from_location_meter' => 0,
                                    'address_text'                 => 'Disinkronkan via BAP Manual (' . $record->issue_category_label . ')',
                                    'note'                         => 'Disetujui via BAP: ' . $record->reason,
                                ]
                            );

                            $attendance->update(['checkout_log_id' => $checkoutLog->id]);
                        }

                        // 4. Update BAP Record
                        $record->update([
                            'checkin_time'     => $checkinTime,
                            'checkout_time'    => $checkoutTime,
                            'status'           => 'approved',
                            'approved_by'      => auth()->id(),
                            'approved_at'      => now(),
                            'attendance_id'    => $attendance->id,
                            'rejection_reason' => $data['admin_notes'] ?? null,
                        ]);

                        // 5. Kirim Push Notification ke karyawan
                        try {
                            if ($record->employee?->fcm_token) {
                                FirebaseService::sendNotification(
                                    $record->employee->fcm_token,
                                    '✅ Pengajuan BAP Disetujui',
                                    "Pengajuan bukti absensi manual tanggal {$record->date->format('d M Y')} telah disetujui.",
                                    ['type' => 'bap_status_update', 'bap_id' => (string)$record->id]
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::warning('FCM BAP error: ' . $e->getMessage());
                        }

                        Notification::make()
                            ->title('✅ BAP Berhasil Disetujui')
                            ->body("Kehadiran {$record->employee?->full_name} pada {$record->date->format('d M Y')} berhasil dicatat.")
                            ->success()
                            ->send();
                    }),

                // ─── ACTION TOLAK (REJECT) ────────────────────────────────────
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BapRequest $record): bool => $record->status === 'pending')
                    ->modalHeading('Tolak Pengajuan BAP')
                    ->modalDescription('Harap berikan alasan penolakan agar karyawan mengetahui mengapa bukti absensi ditolak.')
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->placeholder('Contoh: Foto screenshot buram/tidak mencantumkan timestamp yang jelas...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (BapRequest $record, array $data): void {
                        $record->update([
                            'status'           => 'rejected',
                            'approved_by'      => auth()->id(),
                            'approved_at'      => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        try {
                            if ($record->employee?->fcm_token) {
                                FirebaseService::sendNotification(
                                    $record->employee->fcm_token,
                                    '❌ Pengajuan BAP Ditolak',
                                    "Pengajuan BAP tanggal {$record->date->format('d M Y')} ditolak: {$data['rejection_reason']}",
                                    ['type' => 'bap_status_update', 'bap_id' => (string)$record->id]
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::warning('FCM BAP error: ' . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Pengajuan BAP Ditolak')
                            ->body("Pengajuan BAP {$record->employee?->full_name} telah ditolak.")
                            ->danger()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
