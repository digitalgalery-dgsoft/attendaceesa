<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingGroupResource\Pages;
use App\Models\WorkingGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkingGroupResource extends Resource
{
    protected static ?string $model = WorkingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('General Information')
                ->components([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('region')
                        ->maxLength(255),
                    TextInput::make('area')
                        ->maxLength(255),
                    TextInput::make('sub_area')
                        ->maxLength(255),
                    DatePicker::make('data_applied_date')
                        ->label('Data Applied (Tanggal Berlaku)'),
                ])->columns(2)->columnSpanFull(),

            Section::make('Days Applied (Detail Harian)')
                ->components([
                    Repeater::make('rules')
                        ->relationship()
                        ->schema([
                            Select::make('day_of_week')
                                ->options([
                                    'Monday'    => 'Senin',
                                    'Tuesday'   => 'Selasa',
                                    'Wednesday' => 'Rabu',
                                    'Thursday'  => 'Kamis',
                                    'Friday'    => 'Jumat',
                                    'Saturday'  => 'Sabtu',
                                    'Sunday'    => 'Minggu',
                                ])
                                ->required()
                                ->columnSpan(2),
                            Select::make('shift_id')
                                ->relationship('shift', 'name')
                                ->required()
                                ->columnSpan(3),
                            TextInput::make('late_tolerance')
                                ->label('Late Tol. (Mins)')
                                ->numeric()
                                ->default(15)
                                ->required()
                                ->columnSpan(2),
                            Select::make('store_assignment_id')
                                ->relationship('storeAssignment', 'name')
                                ->label('Location Assignment')
                                ->columnSpan(3),
                            Toggle::make('routing_active')
                                ->label('Routing Active')
                                ->default(false)
                                ->columnSpan(2),
                        ])
                        ->columns(12)
                        ->defaultItems(7)
                        ->disableItemMovement(),
                ])->columnSpanFull(),

            Section::make('List Nama (Anggota Grup)')
                ->components([
                    Repeater::make('members')
                        ->relationship()
                        ->schema([
                            Select::make('employee_id')
                                ->relationship('employee', 'first_name')
                                ->searchable()
                                ->required()
                                ->columnSpan(3),
                            Select::make('master_shift_id')
                                ->relationship('shift', 'name')
                                ->label('Master Shift')
                                ->required()
                                ->columnSpan(3),
                            TextInput::make('late_tolerance')
                                ->label('Late Tol. (Mins)')
                                ->numeric()
                                ->default(15)
                                ->required()
                                ->columnSpan(2),
                            Select::make('first_visit_store_id')
                                ->relationship('firstVisitStore', 'name')
                                ->label('First Visit Store')
                                ->columnSpan(4),
                        ])
                        ->columns(12),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('region')
                    ->searchable(),
                TextColumn::make('area')
                    ->searchable(),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Total Members'),
                TextColumn::make('data_applied_date')
                    ->date()
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
            'index'  => Pages\ListWorkingGroups::route('/'),
            'create' => Pages\CreateWorkingGroup::route('/create'),
            'edit'   => Pages\EditWorkingGroup::route('/{record}/edit'),
        ];
    }
}
