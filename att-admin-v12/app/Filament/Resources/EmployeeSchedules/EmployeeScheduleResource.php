<?php

namespace App\Filament\Resources\EmployeeSchedules;

use App\Filament\Resources\EmployeeSchedules\Pages\CreateEmployeeSchedule;
use App\Filament\Resources\EmployeeSchedules\Pages\EditEmployeeSchedule;
use App\Filament\Resources\EmployeeSchedules\Pages\ListEmployeeSchedules;
use App\Filament\Resources\EmployeeSchedules\Schemas\EmployeeScheduleForm;
use App\Filament\Resources\EmployeeSchedules\Tables\EmployeeSchedulesTable;
use App\Models\EmployeeSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmployeeScheduleResource extends Resource
{
    protected static ?string $model = EmployeeSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance & Time Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Roster Individual';

    public static function form(Schema $schema): Schema
    {
        return EmployeeScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\EmployeeSchedules\Pages\EmployeeScheduleRoster::route('/'),
            'create' => \App\Filament\Resources\EmployeeSchedules\Pages\CreateEmployeeSchedule::route('/create'),
            'edit' => \App\Filament\Resources\EmployeeSchedules\Pages\EditEmployeeSchedule::route('/{record}/edit'),
            'list' => \App\Filament\Resources\EmployeeSchedules\Pages\ListEmployeeSchedules::route('/list'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->can('manage_roster');
    }
}
