<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlastInfoResource\Pages;
use App\Models\BlastInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\Get;
use BackedEnum;

class BlastInfoResource extends Resource
{
    protected static ?string $model = BlastInfo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|\UnitEnum|null $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Blast Infos';
    
    public static function getNavigationGroup(): ?string
    {
        return 'Settings'; // Or maybe General / Employee Management depending on existing groups
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        
        // Super Admin and Administrator can view all
        if ($user->hasRole(['Super Admin', 'Administrator'])) {
            return $query;
        }
        
        // Head can only see their department's blast info
        if ($user->hasRole('Head') && $user->employee) {
            return $query->where('target_type', 'department')
                         ->where('department_id', $user->employee->department_id);
        }
        
        // Others cannot see
        return $query->where('id', 0);
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('target_type')
                    ->options([
                        'all' => 'Semua Karyawan',
                        'department' => 'Departemen Tertentu',
                    ])
                    ->required()
                    ->reactive()
                    ->default(function () {
                        $user = auth()->user();
                        if ($user->hasRole('Head')) {
                            return 'department';
                        }
                        return 'all';
                    })
                    ->disabled(fn () => auth()->user()->hasRole('Head'))
                    ->dehydrated(),
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required(fn (Get $get) => $get('target_type') === 'department')
                    ->visible(fn (Get $get) => $get('target_type') === 'department')
                    ->default(function () {
                        $user = auth()->user();
                        if ($user->hasRole('Head') && $user->employee) {
                            return $user->employee->department_id;
                        }
                        return null;
                    })
                    ->disabled(fn () => auth()->user()->hasRole('Head'))
                    ->dehydrated(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'all' ? 'Semua' : 'Departemen'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListBlastInfos::route('/'),
            'create' => Pages\CreateBlastInfo::route('/create'),
            'edit' => Pages\EditBlastInfo::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['Super Admin', 'Administrator', 'Head']);
    }
}
