<?php

namespace App\Filament\Resources\LocationRequests\Tables;

use App\Models\LocationRequest;
use App\Models\WorkLocation;
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

class LocationRequestsTable
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
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Approval',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Toko / Lokasi')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (LocationRequest $record): ?string => $record->address ? \Illuminate\Support\Str::limit($record->address, 45) : null),

                TextColumn::make('employee.full_name')
                    ->label('Karyawan Pengaju')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('branch.name')
                    ->label('Cabang / Area')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('maps_url')
                    ->label('Google Maps')
                    ->formatStateUsing(fn ($state) => $state ? 'Buka Peta ↗' : '-')
                    ->url(fn (LocationRequest $record) => $record->maps_url ?: ($record->latitude && $record->longitude ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" : null), true)
                    ->color('primary')
                    ->icon('heroicon-m-arrow-top-right-on-square'),

                TextColumn::make('coordinates')
                    ->label('Koordinat GPS')
                    ->getStateUsing(fn (LocationRequest $record) => $record->latitude && $record->longitude ? "{$record->latitude}, {$record->longitude}" : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('radius_meter')
                    ->label('Radius')
                    ->formatStateUsing(fn ($state) => "{$state} m")
                    ->sortable(),

                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Tgl Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Approval',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                SelectFilter::make('principal_id')
                    ->label('Prinsiple')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Cabang / Area')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LocationRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Persetujuan Lokasi Baru')
                    ->modalDescription(fn (LocationRequest $record) => "Lokasi '{$record->name}' akan otomatis ditambahkan ke Master Work Locations dan dapat langsung digunakan untuk presensi/kunjungan.")
                    ->modalSubmitActionLabel('Ya, Setujui & Tambahkan ke Master')
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Toko / Lokasi di Master')
                            ->default(fn (LocationRequest $record) => $record->name)
                            ->required(),

                        TextInput::make('radius_meter')
                            ->label('Radius Toleransi (Meter)')
                            ->numeric()
                            ->default(fn (LocationRequest $record) => $record->radius_meter ?: 100)
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Catatan Approval')
                            ->placeholder('Opsional: Catatan dari admin...')
                            ->rows(2),
                    ])
                    ->action(function (LocationRequest $record, array $data): void {
                        // 1. Buat record WorkLocation baru
                        $workLocation = WorkLocation::create([
                            'name' => $data['name'] ?: $record->name,
                            'type' => $record->type ?: 'client',
                            'address' => $record->address,
                            'latitude' => $record->latitude,
                            'longitude' => $record->longitude,
                            'radius_meter' => (int) ($data['radius_meter'] ?: 100),
                            'principal_id' => $record->principal_id,
                            'branch_id' => $record->branch_id,
                            'company_id' => $record->company_id ?: 1,
                            'is_active' => true,
                        ]);

                        // 2. Update status pengajuan lokasi
                        $record->update([
                            'name' => $data['name'] ?: $record->name,
                            'radius_meter' => (int) ($data['radius_meter'] ?: 100),
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'work_location_id' => $workLocation->id,
                            'admin_notes' => $data['admin_notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('✅ Pengajuan Lokasi Disetujui')
                            ->body("Toko '{$workLocation->name}' berhasil ditambahkan ke Master Work Locations.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LocationRequest $record): bool => $record->status === 'pending')
                    ->modalHeading('Tolak Pengajuan Lokasi')
                    ->modalDescription('Harap berikan alasan penolakan agar karyawan dapat memperbaiki pengajuan lokasi.')
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Alasan Penolakan')
                            ->placeholder('Contoh: Titik koordinat tidak sesuai atau toko sudah terdaftar dengan nama lain...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (LocationRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'admin_notes' => $data['admin_notes'],
                        ]);

                        Notification::make()
                            ->title('Pengajuan Lokasi Ditolak')
                            ->body("Pengajuan toko '{$record->name}' telah ditolak.")
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
