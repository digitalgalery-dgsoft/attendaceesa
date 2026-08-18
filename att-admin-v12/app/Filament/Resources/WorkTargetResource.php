<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkTargetResource\Pages;
use App\Models\WorkTarget;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkTargetResource extends Resource
{
    protected static ?string $model = WorkTarget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = '2. Employee Management';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Employee Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->relationship('employee', 'full_name')
                ->searchable()
                ->required(),
            TextInput::make('month_year')
                ->label('Bulan / Tahun (Contoh: 08-2026)')
                ->placeholder('MM-YYYY')
                ->required(),
            TextInput::make('target_hk')
                ->label('Target Hari Kerja')
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('month_year')
                    ->label('Masa (Bulan-Tahun)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_hk')
                    ->label('Target HK')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkTargets::route('/'),
            'create' => Pages\CreateWorkTarget::route('/create'),
            'edit'   => Pages\EditWorkTarget::route('/{record}/edit'),
        ];
    }
}
