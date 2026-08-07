<?php

namespace App\Filament\Resources\SalesReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SalesReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->required(),
                Select::make('attendance_log_id')
                    ->relationship('attendanceLog', 'id'),
                TextInput::make('store_name')
                    ->label('Nama Toko/Outlet')
                    ->required(),
                Select::make('oos_status')
                    ->label('Out of Stock (OOS)')
                    ->options([
                        'Aman' => 'Aman',
                        'Kosong' => 'Kosong',
                    ]),
                Textarea::make('oos_notes')->label('Catatan OOS'),
                FileUpload::make('photo_oos')
                    ->label('Foto OOS')
                    ->image(),
                Select::make('plano_status')
                    ->label('Planogram')
                    ->options([
                        'Sesuai' => 'Sesuai',
                        'Tidak Sesuai' => 'Tidak Sesuai',
                    ]),
                Textarea::make('plano_notes')->label('Catatan Planogram'),
                FileUpload::make('photo_plano')
                    ->label('Foto Planogram')
                    ->image(),
                Select::make('promo_status')
                    ->label('Promo')
                    ->options([
                        'Berjalan' => 'Berjalan',
                        'Tidak Berjalan' => 'Tidak Berjalan',
                    ]),
                Textarea::make('promo_notes')->label('Catatan Promo'),
                FileUpload::make('photo_promo')
                    ->label('Foto Promo')
                    ->image(),
                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
                DatePicker::make('report_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('submitted'),
                TextInput::make('location'),
                Textarea::make('ai_insights')
                    ->columnSpanFull(),
            ]);
    }
}
