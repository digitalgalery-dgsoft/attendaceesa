<?php

namespace App\Filament\Resources\ReportSubmissions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Laporan & Lokasi')
                    ->description('Detail waktu penyerahan, promotor, toko, dan koordinat GPS.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('submission_code')
                                ->label('Nomor Laporan')
                                ->disabled(),
                            DateTimePicker::make('submitted_at')
                                ->label('Waktu Submit')
                                ->disabled(),
                            Select::make('status')
                                ->label('Status Verifikasi')
                                ->options([
                                    'submitted' => 'Menunggu Verifikasi',
                                    'verified' => 'Terverifikasi (Valid)',
                                    'rejected' => 'Ditolak (Tidak Sesuai)',
                                ])
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('template_name')
                                ->label('Jenis Form Pelaporan')
                                ->formatStateUsing(fn ($record) => $record?->template?->title ?? '-')
                                ->disabled(),
                            TextInput::make('employee_name')
                                ->label('Nama Promotor / SPG')
                                ->formatStateUsing(fn ($record) => ($record?->employee?->full_name ?? '-') . ' (' . ($record?->employee?->nik ?? '-') . ')')
                                ->disabled(),
                            TextInput::make('principal_name')
                                ->label('Prinsiple / Brand')
                                ->formatStateUsing(fn ($record) => $record?->principal?->name ?? '-')
                                ->disabled(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('store_name')
                                ->label('Nama Toko / Outlet')
                                ->disabled(),
                            TextInput::make('coordinates')
                                ->label('Koordinat GPS')
                                ->formatStateUsing(fn ($record) => $record ? "{$record->latitude}, {$record->longitude}" : '-')
                                ->disabled(),
                            Toggle::make('is_within_radius')
                                ->label('Validasi Dalam Radius Toko')
                                ->disabled(),
                        ]),
                        Textarea::make('address')
                            ->label('Alamat / Lokasi Geocoding')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Isian Form & Foto Hasil Pelaporan')
                    ->description('Jawaban dari setiap parameter pertanyaan beserta bukti foto ber-watermark.')
                    ->schema([
                        Repeater::make('values')
                            ->relationship('values')
                            ->label('Daftar Parameter Input')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_label')
                                        ->label('Parameter / Pertanyaan')
                                        ->disabled(),
                                    TextInput::make('display_value')
                                        ->label('Jawaban / Nilai Input')
                                        ->formatStateUsing(function ($record) {
                                            if (!$record) return '-';
                                            if (!empty($record->value_text)) return $record->value_text;
                                            if (!empty($record->value_number)) return number_format($record->value_number);
                                            if (!empty($record->value_json)) {
                                                return is_array($record->value_json) ? implode(', ', $record->value_json) : json_encode($record->value_json);
                                            }
                                            return '-';
                                        })
                                        ->disabled(),
                                    TextInput::make('watermark_text')
                                        ->label('Info Stempel / Watermark')
                                        ->disabled(),
                                ]),
                                FileUpload::make('photo_url')
                                    ->label('Bukti Foto')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->disabled()
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => !empty($record?->photo_url)),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan & Verifikasi Admin')
                    ->schema([
                        Textarea::make('verification_notes')
                            ->label('Catatan Verifikasi / Evaluasi')
                            ->placeholder('Tuliskan catatan arahan atau alasan jika laporan ditolak...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
