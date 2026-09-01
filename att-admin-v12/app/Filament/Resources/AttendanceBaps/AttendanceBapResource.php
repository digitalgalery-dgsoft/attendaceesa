<?php

namespace App\Filament\Resources\AttendanceBaps;

use App\Filament\Resources\AttendanceBaps\Pages\CreateAttendanceBap;
use App\Filament\Resources\AttendanceBaps\Pages\EditAttendanceBap;
use App\Filament\Resources\AttendanceBaps\Pages\ListAttendanceBaps;
use App\Filament\Resources\AttendanceBaps\Pages\ViewAttendanceBap;
use App\Filament\Resources\AttendanceBaps\Schemas\AttendanceBapForm;
use App\Filament\Resources\AttendanceBaps\Tables\AttendanceBapsTable;
use App\Models\BapRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AttendanceBapResource extends Resource
{
    protected static ?string $model = BapRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance & Time Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Pengajuan BAP (Bukti Absen)';
    protected static ?string $modelLabel = 'Pengajuan BAP';
    protected static ?string $pluralModelLabel = 'Pengajuan BAP (Bukti Absen Manual)';

    public static function getNavigationBadge(): ?string
    {
        $count = BapRequest::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceBapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceBapsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAttendanceBaps::route('/'),
            'create' => CreateAttendanceBap::route('/create'),
            'view'   => ViewAttendanceBap::route('/{record}'),
            'edit'   => EditAttendanceBap::route('/{record}/edit'),
        ];
    }
}
