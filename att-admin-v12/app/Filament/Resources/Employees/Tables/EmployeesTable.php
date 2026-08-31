<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
                    ->searchable(query: function (Builder $query, string $search) {
                        $driver = DB::getDriverName();
                        if ($driver === 'pgsql') {
                            $query->where('employee_no', 'ilike', "%{$search}%");
                        } else {
                            $query->where('employee_no', 'like', "%{$search}%");
                        }
                    }),
                TextColumn::make('full_name')
                    ->label('Nama Karyawan')
                    ->searchable(query: function (Builder $query, string $search) {
                        $driver = DB::getDriverName();
                        if ($driver === 'pgsql') {
                            $query->where('full_name', 'ilike', "%{$search}%");
                        } else {
                            $query->where('full_name', 'like', "%{$search}%");
                        }
                    }),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->default('-'),
                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->sortable()
                    ->searchable()
                    ->default('-'),
                TextColumn::make('branch.name')
                    ->label('Area')
                    ->sortable()
                    ->searchable()
                    ->default('-'),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('supervisor.full_name')
                    ->label('Supervisor / Leader')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('user.name')
                    ->label('User Account')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->placeholder('Belum Terhubung')
                    ->icon(fn ($state, $record) => !empty($record->device_id) ? 'heroicon-m-device-phone-mobile' : null)
                    ->color(fn ($state, $record) => !empty($record->device_id) ? 'primary' : 'gray')
                    ->tooltip(fn ($record) => !empty($record->device_id) ? "Device ID: {$record->device_id}" : 'Belum ada perangkat yang terhubung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                \Filament\Tables\Filters\SelectFilter::make('status_karyawan')
                    ->label('Status Karyawan')
                    ->options([
                        'active' => 'Aktif (Default)',
                        'resigned' => 'Resign / Non-Aktif',
                        'all' => 'Semua Status',
                    ])
                    ->default('active')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $value = $data['value'] ?? 'active';
                        if ($value === 'active') {
                            $query->where('is_active', true);
                        } elseif ($value === 'resigned') {
                            $query->where('is_active', false);
                        }
                    }),
                \Filament\Tables\Filters\SelectFilter::make('company_id')
                    ->label('Filter Company')
                    ->searchable()
                    ->preload()
                    ->relationship('company', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('principal_id')
                    ->label('Filter Prinsiple')
                    ->searchable()
                    ->preload()
                    ->relationship('principal', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                        $query->where('is_active', true);
                        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                            $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                        }
                        return $query->orderBy('name');
                    }),
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
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
                    \Filament\Actions\BulkAction::make('assign_supervisor')
                        ->label('Atur SPV / Leader Massal')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->modalHeading('Tetapkan Supervisor / Leader Massal')
                        ->modalDescription('Pilih Supervisor / Leader yang akan ditugaskan untuk seluruh karyawan yang dicentang.')
                        ->modalSubmitActionLabel('Simpan SPV')
                        ->form([
                            \Filament\Forms\Components\Select::make('supervisor_id')
                                ->label('Pilih Supervisor / Leader')
                                ->placeholder('-- Cari Nama / NIK SPV --')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    return \App\Models\Employee::where('is_active', true)
                                        ->where(function ($q) use ($search) {
                                            $q->where('full_name', 'ilike', "%{$search}%")
                                              ->orWhere('employee_no', 'ilike', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($emp) {
                                            $pos = $emp->position ? " ({$emp->position->name})" : '';
                                            return [$emp->id => "{$emp->full_name} [NIK: {$emp->employee_no}]{$pos}"];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $emp = \App\Models\Employee::find($value);
                                    if (!$emp) return null;
                                    $pos = $emp->position ? " ({$emp->position->name})" : '';
                                    return "{$emp->full_name} [NIK: {$emp->employee_no}]{$pos}";
                                })
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $supervisor = \App\Models\Employee::find($data['supervisor_id']);
                            if (!$supervisor) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal: Supervisor tidak ditemukan')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->id !== $supervisor->id) {
                                    $record->update(['supervisor_id' => $supervisor->id]);
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Supervisor Berhasil Ditetapkan')
                                ->body("Berhasil menetapkan **{$supervisor->full_name}** sebagai Supervisor untuk **{$count} karyawan**.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('clear_supervisor')
                        ->label('Hapus SPV / Leader Massal')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Penugasan Supervisor')
                        ->modalDescription('Apakah Anda yakin ingin mengosongkan nama supervisor untuk seluruh karyawan yang dipilih?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['supervisor_id' => null]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Supervisor Berhasil Dikosongkan')
                                ->body("Berhasil menghapus supervisor untuk **{$count} karyawan**.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('delete_resigned_selected')
                        ->label('Hapus Karyawan Resign Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Hapus Data Karyawan Resign Terpilih')
                        ->modalDescription('Sistem akan memproses penghapusan HANYA untuk karyawan yang berstatus Resign / Non-Aktif (is_active = false) di antara baris yang dicentang. Karyawan aktif akan otomatis dilewati & aman terlindungi.')
                        ->modalSubmitActionLabel('Hapus Data Resign Terpilih')
                        ->form([
                            \Filament\Forms\Components\Radio::make('deletion_type')
                                ->label('Metode Penghapusan')
                                ->options([
                                    'soft' => 'Soft Delete (Pindahkan ke Sampah) - Aman & masih dapat dipulihkan melalui filter Trash',
                                    'force' => 'Permanent / Force Delete - Hapus bersih total dari database',
                                ])
                                ->default('soft')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $resignedRecords = $records->filter(fn ($r) => !$r->is_active);
                            $resignedCount = $resignedRecords->count();

                            if ($resignedCount === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Tidak Ada Karyawan Resign')
                                    ->body('Tidak ada karyawan berstatus Resign di antara data yang Anda centang. Karyawan aktif dilindungi.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $isForce = ($data['deletion_type'] ?? 'soft') === 'force';
                            $ids = $resignedRecords->pluck('id')->toArray();

                            try {
                                if ($isForce) {
                                    \App\Models\Employee::whereIn('supervisor_id', $ids)->update(['supervisor_id' => null]);
                                    foreach (array_chunk($ids, 500) as $chunkIds) {
                                        \App\Models\Employee::withTrashed()->whereIn('id', $chunkIds)->forceDelete();
                                    }
                                } else {
                                    foreach (array_chunk($ids, 500) as $chunkIds) {
                                        \App\Models\Employee::whereIn('id', $chunkIds)->delete();
                                    }
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Karyawan Resign Berhasil Dihapus')
                                    ->body("Berhasil menghapus **{$resignedCount} karyawan resign** (dari total {$records->count()} data terpilih) secara " . ($isForce ? 'Permanen (Force Delete)' : 'Soft Delete (Masuk Sampah)') . ".")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Menghapus')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
