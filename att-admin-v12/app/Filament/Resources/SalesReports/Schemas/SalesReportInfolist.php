<?php

namespace App\Filament\Resources\SalesReports\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SalesReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.full_name')
                    ->label('Employee'),
                TextEntry::make('attendanceLog.id')
                    ->label('Attendance log')
                    ->placeholder('-'),
                TextEntry::make('client_name'),
                TextEntry::make('client_company')
                    ->placeholder('-'),
                TextEntry::make('revenue')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('report_date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('location')
                    ->placeholder('-'),
                ImageEntry::make('receipt_image')
                    ->placeholder('-'),
                TextEntry::make('ai_insights')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
