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
                TextInput::make('client_name')
                    ->required(),
                TextInput::make('client_company'),
                TextInput::make('revenue')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DatePicker::make('report_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('location'),
                FileUpload::make('receipt_image')
                    ->image(),
                Textarea::make('ai_insights')
                    ->columnSpanFull(),
            ]);
    }
}
