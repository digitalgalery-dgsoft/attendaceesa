<?php

namespace App\Filament\Resources\ReportSubmissions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                                    'pending' => 'Menunggu Verifikasi',
                                    'submitted' => 'Menunggu Verifikasi',
                                    'approved' => 'Terverifikasi (Valid)',
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
                                ->formatStateUsing(fn ($record) => $record?->workLocation?->name ?? $record?->itineraryItem?->destination ?? $record?->store_name ?? 'Kunjungan Toko')
                                ->disabled(),
                            TextInput::make('coordinates')
                                ->label('Koordinat GPS')
                                ->formatStateUsing(fn ($record) => ($record && $record->latitude) ? "{$record->latitude}, {$record->longitude}" : '-')
                                ->disabled(),
                            Toggle::make('is_within_radius')
                                ->label('Validasi Radius Toko')
                                ->disabled(),
                        ]),
                        Textarea::make('address')
                            ->label('Alamat / Lokasi Geocoding')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record?->address)),
                    ]),

                Section::make('Isian Form & Foto Hasil Pelaporan')
                    ->description('Jawaban dari setiap parameter pertanyaan beserta bukti foto terlampir.')
                    ->schema([
                        Repeater::make('values')
                            ->relationship('values')
                            ->label('Daftar Parameter Input')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field_name')
                                        ->label('Parameter / Pertanyaan')
                                        ->formatStateUsing(function ($state, $record) {
                                            return $record?->formField?->field_label ?? ucwords(str_replace('_', ' ', (string) $state));
                                        })
                                        ->disabled(),
                                    TextInput::make('display_value')
                                        ->label('Jawaban / Nilai Input')
                                        ->formatStateUsing(function ($state, $record) {
                                            if (!$record) return '-';
                                            if (!empty($record->media_url)) {
                                                return '📷 Bukti Foto / Tanda Tangan Terlampir';
                                            }
                                            if ($record->field_type === 'currency' && $record->value_number !== null) {
                                                return 'Rp ' . number_format((float) $record->value_number, 0, ',', '.');
                                            }
                                            if ($record->value_number !== null) {
                                                return (string) $record->value_number;
                                            }
                                            if (!empty($record->value_json)) {
                                                return is_array($record->value_json) ? implode(', ', $record->value_json) : json_encode($record->value_json);
                                            }
                                            if (!empty($record->value_text)) {
                                                return (string) $record->value_text;
                                            }
                                            return '-';
                                        })
                                        ->disabled(),
                                ]),
                                Placeholder::make('media_preview')
                                    ->label('Preview Foto Bukti / Tanda Tangan')
                                    ->content(function ($record) {
                                        if (empty($record?->media_url)) return null;
                                        $url = asset('storage/' . $record->media_url);
                                        return new HtmlString("
                                            <div style='margin-top: 4px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;'>
                                                <div style='display: flex; gap: 14px; align-items: flex-start; flex-wrap: wrap;'>
                                                    <a href='{$url}' target='_blank' style='display: inline-block;'>
                                                        <img src='{$url}' style='max-height: 260px; max-width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; object-fit: contain; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06);' />
                                                    </a>
                                                </div>
                                                <div style='font-size: 11.5px; color: #64748b; margin-top: 6px; font-weight: 500;'>
                                                    🔗 <a href='{$url}' target='_blank' style='color: #0284c7; text-decoration: underline;'>Klik foto untuk melihat / mengunduh resolusi asli</a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->visible(fn ($record) => !empty($record?->media_url))
                                    ->columnSpanFull(),
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
