<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Models\Holiday;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Holidays';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    protected static ?int $navigationSort = 8;

    public static function canViewAny(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('view_holidays'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('holiday_date')
                ->label('Tanggal Libur')
                ->required(),
            TextInput::make('name')
                ->label('Nama Hari Libur')
                ->required()
                ->maxLength(150),
            Select::make('type')
                ->label('Jenis')
                ->options([
                    'national' => 'Nasional',
                    'company'  => 'Perusahaan',
                    'regional' => 'Regional',
                ])
                ->required(),
            Toggle::make('is_paid')
                ->label('Libur Dibayar?')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('holiday_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Hari Libur')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'national' => 'danger',
                        'company'  => 'warning',
                        'regional' => 'info',
                        default    => 'gray',
                    }),
                IconColumn::make('is_paid')
                    ->label('Dibayar')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'national' => 'Nasional',
                        'company'  => 'Perusahaan',
                        'regional' => 'Regional',
                    ]),
            ])
            ->defaultSort('holiday_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHolidays::route('/'),
            'create' => CreateHoliday::route('/create'),
            'edit'   => EditHoliday::route('/{record}/edit'),
        ];
    }
}
