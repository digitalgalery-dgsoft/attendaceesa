<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Principal;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete_resigned')
                ->label('Hapus Karyawan Resign')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Hapus / Bersihkan Data Karyawan Resign')
                ->modalDescription('Pilih filter dan metode penghapusan untuk data karyawan yang berstatus Resign / Non-Aktif (is_active = false).')
                ->modalSubmitActionLabel('Jalankan Penghapusan')
                ->form([
                    Placeholder::make('summary_info')
                        ->label('Status Data Resign Saat Ini')
                        ->content(function () {
                            $query = Employee::where('is_active', false);
                            if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                                $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                            }
                            $totalResign = $query->count();
                            $totalActive = Employee::where('is_active', true)->count();
                            return new HtmlString("
                                <div class='p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm space-y-1'>
                                    <div class='font-medium text-danger-600 dark:text-danger-400'>
                                        📌 Total Karyawan Resign / Non-Aktif: <strong>" . number_format($totalResign, 0, ',', '.') . " orang</strong>
                                    </div>
                                    <div class='text-xs text-gray-500 dark:text-gray-400'>
                                        Total Karyawan Aktif: <strong>" . number_format($totalActive, 0, ',', '.') . " orang</strong> (Aman terlindungi & tidak akan terhapus)
                                    </div>
                                </div>
                            ");
                        }),
                    Select::make('principal_id')
                        ->label('Filter Prinsiple')
                        ->placeholder('-- Semua Prinsiple --')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $query = Principal::where('is_active', true);
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                            }
                            return $query->orderBy('name')->pluck('name', 'id')->toArray();
                        }),
                    Select::make('company_id')
                        ->label('Filter Company')
                        ->placeholder('-- Semua Company --')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Company::orderBy('name')->pluck('name', 'id')->toArray()),
                    Select::make('branch_id')
                        ->label('Filter Area / Cabang')
                        ->placeholder('-- Semua Area / Cabang --')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $query = Branch::query();
                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                                $query->whereIn('id', auth()->user()->getAccessibleBranchIds());
                            }
                            return $query->orderBy('name')->pluck('name', 'id')->toArray();
                        }),
                    DatePicker::make('resign_before')
                        ->label('Resign Sebelum Tanggal (Opsional)')
                        ->placeholder('Pilih tanggal batas')
                        ->helperText('Kosongkan jika ingin menghapus semua tanggal resign tanpa batasan waktu.'),
                    Radio::make('deletion_type')
                        ->label('Metode Penghapusan')
                        ->options([
                            'soft' => 'Soft Delete (Pindahkan ke Sampah) - Aman & masih dapat dipulihkan melalui filter Trash',
                            'force' => 'Permanent / Force Delete - Menghapus bersih total dari database',
                        ])
                        ->default('soft')
                        ->required(),
                    Checkbox::make('confirm')
                        ->label('Saya mengonfirmasi untuk menghapus data karyawan berstatus resign yang sesuai kriteria di atas')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $query = Employee::where('is_active', false);
                    
                    if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                        $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                    }

                    if (!empty($data['principal_id'])) {
                        $query->where('principal_id', $data['principal_id']);
                    }
                    if (!empty($data['company_id'])) {
                        $query->where('company_id', $data['company_id']);
                    }
                    if (!empty($data['branch_id'])) {
                        $query->where('branch_id', $data['branch_id']);
                    }
                    if (!empty($data['resign_before'])) {
                        $query->where('resign_date', '<=', $data['resign_before']);
                    }

                    $isForce = ($data['deletion_type'] ?? 'soft') === 'force';
                    
                    if ($isForce) {
                        $ids = (clone $query)->withTrashed()->pluck('id')->toArray();
                    } else {
                        $ids = (clone $query)->whereNull('deleted_at')->pluck('id')->toArray();
                    }

                    $count = count($ids);

                    if ($count === 0) {
                        Notification::make()
                            ->title('Tidak Ada Data')
                            ->body('Tidak ditemukan data karyawan resign yang cocok dengan kriteria filter yang dipilih.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        if ($isForce) {
                            // Set bawahan yang memiliki supervisor karyawan ini menjadi null
                            Employee::whereIn('supervisor_id', $ids)->update(['supervisor_id' => null]);
                            
                            // Chunk deletion agar performa tetap ringan
                            foreach (array_chunk($ids, 500) as $chunkIds) {
                                Employee::withTrashed()->whereIn('id', $chunkIds)->forceDelete();
                            }
                        } else {
                            foreach (array_chunk($ids, 500) as $chunkIds) {
                                Employee::whereIn('id', $chunkIds)->delete();
                            }
                        }

                        Notification::make()
                            ->title('Pembersihan Karyawan Resign Selesai')
                            ->body("Berhasil menghapus **{$count} data karyawan resign** secara " . ($isForce ? 'Permanen (Force Delete)' : 'Soft Delete (Masuk Sampah)') . ".")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menghapus Data')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ImportAction::make()
                ->importer(\App\Filament\Imports\EmployeeImporter::class),
            CreateAction::make(),
        ];
    }
}
