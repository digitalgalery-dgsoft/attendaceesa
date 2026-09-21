<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Exports\ShiftTemplateExport;
use App\Filament\Resources\Shifts\ShiftResource;
use App\Imports\ShiftImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_shift')
                ->label('Import Shift (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import Master Shift (Excel / CSV)')
                ->modalDescription('Unggah berkas Excel (.xlsx / .xls) atau CSV berisi daftar shift kerja. Kolom wajib: Nama Shift, Jam Masuk, Jam Pulang. Format waktu mendukung 08:00 maupun 08.00.')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel / CSV')
                        ->disk('local')
                        ->directory('temp-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'application/csv',
                            'text/plain',
                            'application/octet-stream',
                        ])
                        ->required()
                        ->helperText('Gunakan tombol "Download Template Excel" di sebelah kanan untuk format kolom resmi.'),
                ])
                ->action(function (array $data) {
                    $relativePath = $data['file'];
                    $filePath = Storage::disk('local')->exists($relativePath)
                        ? Storage::disk('local')->path($relativePath)
                        : storage_path('app/' . $relativePath);

                    try {
                        $import = new ShiftImport();
                        Excel::import($import, $filePath);

                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }

                        if ($import->importedCount === 0) {
                            $errorList = !empty($import->errors)
                                ? "\n\n📋 Rincian Kendala:\n• " . implode("\n• ", array_slice($import->errors, 0, 5))
                                : "\n\n📋 Kolom yang terbaca: " . (!empty($import->detectedColumns) ? implode(', ', array_slice($import->detectedColumns, 0, 8)) : 'Tidak terdeteksi');

                            if (count($import->errors) > 5) {
                                $errorList .= "\n• ...dan " . (count($import->errors) - 5) . " masalah lainnya.";
                            }

                            Notification::make()
                                ->title('Gagal Mengimpor Shift (0 Data Tersimpan)')
                                ->body("Tidak ada baris shift yang berhasil diproses." . $errorList . "\n\n💡 Saran: Silakan unduh template resmi dari tombol 'Download Template Excel'.")
                                ->danger()
                                ->persistent()
                                ->send();
                        } elseif ($import->skippedCount > 0) {
                            $skipList = "\n\n⚠️ Rincian Baris Dilewati:\n• " . implode("\n• ", array_slice($import->errors, 0, 4));
                            if (count($import->errors) > 4) {
                                $skipList .= "\n• ...dan " . (count($import->errors) - 4) . " baris lainnya.";
                            }

                            Notification::make()
                                ->title('Import Shift Selesai Sebagian')
                                ->body("Berhasil mengimpor/memperbarui {$import->importedCount} data shift.\nNamun ada {$import->skippedCount} baris yang dilewati karena data tidak lengkap/valid." . $skipList)
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Shift Berhasil')
                                ->body("Berhasil mengimpor seluruh {$import->importedCount} data shift kerja.")
                                ->success()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }

                        Notification::make()
                            ->title('Gagal Memproses File')
                            ->body("Terjadi kendala teknis saat membaca file Excel: " . $e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('download_template')
                ->label('Download Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(new ShiftTemplateExport(), 'Template_Import_Shift.xlsx');
                }),

            CreateAction::make(),
        ];
    }
}
