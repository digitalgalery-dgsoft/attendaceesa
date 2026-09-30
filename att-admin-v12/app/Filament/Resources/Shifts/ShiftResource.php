<?php

namespace App\Filament\Resources\Shifts;

use App\Filament\Resources\Shifts\Pages\CreateShift;
use App\Filament\Resources\Shifts\Pages\EditShift;
use App\Filament\Resources\Shifts\Pages\ListShifts;
use App\Filament\Resources\Shifts\Schemas\ShiftForm;
use App\Filament\Resources\Shifts\Tables\ShiftsTable;
use App\Models\Shift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Shifts';

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
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
            'index' => ListShifts::route('/'),
            'create' => CreateShift::route('/create'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $isAdmin = method_exists($user, 'isAdministrator')
            ? $user->isAdministrator()
            : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

        return $isAdmin || $user->can('view_shifts') || $user->can('view_any_shifts');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $isAdmin = method_exists($user, 'isAdministrator')
            ? $user->isAdministrator()
            : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

        if ($isAdmin) {
            return true;
        }

        $principalIds = method_exists($user, 'getAccessiblePrincipalIds')
            ? $user->getAccessiblePrincipalIds()
            : $user->principals()->pluck('principals.id')->toArray();

        return in_array($record->principal_id, $principalIds);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $isAdmin = method_exists($user, 'isAdministrator')
            ? $user->isAdministrator()
            : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

        if ($isAdmin) {
            return true;
        }

        $principalIds = method_exists($user, 'getAccessiblePrincipalIds')
            ? $user->getAccessiblePrincipalIds()
            : $user->principals()->pluck('principals.id')->toArray();

        return in_array($record->principal_id, $principalIds);
    }
}

