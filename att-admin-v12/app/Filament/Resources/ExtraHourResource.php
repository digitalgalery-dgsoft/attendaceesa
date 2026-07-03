<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraHourResource\Pages;
use App\Models\ExtraHour;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExtraHourResource extends Resource
{
    protected static ?string $model = ExtraHour::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->relationship('employee', 'first_name')
                ->searchable()
                ->required(),
            DatePicker::make('date')
                ->required(),
            TimePicker::make('start_time')
                ->required(),
            TimePicker::make('end_time')
                ->required(),
            Toggle::make('cross_day')
                ->label('Melewati Tengah Malam (Cross-Day)')
                ->default(false),
            Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            Select::make('status')
                ->options([
                    'submitted' => 'Submitted',
                    'approved'  => 'Approved',
                    'rejected'  => 'Rejected',
                ])
                ->default('submitted')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->time('H:i')
                    ->sortable(),
                IconColumn::make('cross_day')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                Action::make('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ExtraHour $record) => $record->status === 'submitted')
                    ->action(fn (ExtraHour $record) => $record->update([
                        'status'      => 'approved',
                        'approved_by' => auth()->id(),
                    ])),
                Action::make('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ExtraHour $record) => $record->status === 'submitted')
                    ->action(fn (ExtraHour $record) => $record->update([
                        'status'      => 'rejected',
                        'approved_by' => auth()->id(),
                    ])),
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
            'index'  => Pages\ListExtraHours::route('/'),
            'create' => Pages\CreateExtraHour::route('/create'),
            'edit'   => Pages\EditExtraHour::route('/{record}/edit'),
        ];
    }
}
