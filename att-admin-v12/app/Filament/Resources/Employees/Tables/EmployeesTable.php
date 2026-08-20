<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->circular()
                    ->getStateUsing(function ($record) {
                        if ($record->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo)) {
                            return asset('storage/' . $record->photo);
                        }
                        return null;
                    })
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=7367F0&color=fff'),
                TextColumn::make('employee_no')
                    ->label('NIK / No Karyawan')
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->searchable(),
                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Area')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('supervisor.full_name')
                    ->label('Supervisor')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('User Account')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employment_status')
                    ->badge(),
                TextColumn::make('gender')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('join_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resign_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter Area')
                    ->searchable()
                    ->preload()
                    ->relationship('branch', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                            $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
                        }
                        return $query->orderBy('name');
                    }),
                \Filament\Tables\Filters\SelectFilter::make('principal_id')
                    ->label('Filter Prinsiple')
                    ->searchable()
                    ->preload()
                    ->relationship('principal', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                            $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                        }
                        return $query->orderBy('name');
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('reset_device')
                    ->label('Reset Device')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\Employee $record) {
                        $record->update([
                            'device_id' => null,
                            'device_name' => null,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Device Reset Successfully')
                            ->success()
                            ->send();
                    })
                    ->disabled(fn (\App\Models\Employee $record) => empty($record->device_id)),
                \Filament\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password Mobile')
                    ->modalDescription('Ini akan membuat password baru secara acak untuk akses aplikasi mobile karyawan ini.')
                    ->action(function (\App\Models\Employee $record) {
                        if (!$record->email) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal: Email Kosong')
                                ->body('Karyawan ini tidak memiliki email. Harap isi email karyawan terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $newPassword = \Illuminate\Support\Str::random(8);
                        $record->update([
                            'password' => \Illuminate\Support\Facades\Hash::make($newPassword)
                        ]);

                        try {
                            \Illuminate\Support\Facades\Mail::to($record->email)
                                ->send(new \App\Mail\ResetPasswordMail($record, $newPassword));
                            \Filament\Notifications\Notification::make()
                                ->title('Password Berhasil Direset')
                                ->body("Password baru telah dikirim ke {$record->email}.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Password Direset, tapi Email Gagal Terkirim')
                                ->body('Password baru: ' . $newPassword . ' | Error: ' . $e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
