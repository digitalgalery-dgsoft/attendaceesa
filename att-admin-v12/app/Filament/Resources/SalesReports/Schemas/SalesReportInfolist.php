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
                TextEntry::make('store_name')->label('Nama Toko/Outlet'),
                TextEntry::make('oos_status')->label('Status OOS')->placeholder('-'),
                TextEntry::make('oos_notes')->label('Catatan OOS')->placeholder('-')->columnSpanFull(),
                ImageEntry::make('photo_oos')->label('Foto OOS')->placeholder('-'),
                TextEntry::make('plano_status')->label('Status Planogram')->placeholder('-'),
                TextEntry::make('plano_notes')->label('Catatan Planogram')->placeholder('-')->columnSpanFull(),
                ImageEntry::make('photo_plano')->label('Foto Planogram')->placeholder('-'),
                TextEntry::make('promo_status')->label('Status Promo')->placeholder('-'),
                TextEntry::make('promo_notes')->label('Catatan Promo')->placeholder('-')->columnSpanFull(),
                ImageEntry::make('photo_promo')->label('Foto Promo')->placeholder('-'),
                TextEntry::make('notes')
                    ->label('Catatan Tambahan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('report_date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('location')
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
