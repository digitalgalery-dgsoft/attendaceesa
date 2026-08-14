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
                TextColumn::make('duration')
                    ->label('Total Durasi (menit)')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $hours = floor($state / 60);
                        $mins = $state % 60;
                        return "{$hours}j {$mins}m";
                    }),
                IconColumn::make('cross_day')
                    ->boolean(),
                TextColumn::make('head_approval_status')
                    ->label('Head Approval')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('hrd_approval_status')
                    ->label('HRD Approval')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Final Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'warning',
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
                Action::make('Approve Head')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (ExtraHour $record) {
                        $user = auth()->user();
                        $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                        if ($isAdmin) return $record->head_approval_status === 'pending';
                        if (!$user->employee) return false;
                        return $record->head_approval_status === 'pending' && $record->employee->supervisor_id === $user->employee->id;
                    })
                    ->action(function (ExtraHour $record) {
                        $record->update([
                            'head_approval_status' => 'approved',
                            'head_approved_by'     => auth()->id(),
                            'head_approved_at'     => now(),
                        ]);
                    }),
                Action::make('Reject Head')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (ExtraHour $record) {
                        $user = auth()->user();
                        $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                        if ($isAdmin) return $record->head_approval_status === 'pending';
                        if (!$user->employee) return false;
                        return $record->head_approval_status === 'pending' && $record->employee->supervisor_id === $user->employee->id;
                    })
                    ->action(function (ExtraHour $record) {
                        $record->update([
                            'head_approval_status' => 'rejected',
                            'head_approved_by'     => auth()->id(),
                            'head_approved_at'     => now(),
                            'status'               => 'rejected',
                        ]);
                    }),
                Action::make('Approve HRD')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (ExtraHour $record) {
                        $user = auth()->user();
                        $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                        $isHRD = $user->hasRole(['HRD', 'hrd']) || $isAdmin;
                        return $isHRD && $record->hrd_approval_status === 'pending' && ($record->head_approval_status === 'approved' || is_null($record->employee->supervisor_id));
                    })
                    ->action(function (ExtraHour $record) {
                        $record->update([
                            'hrd_approval_status' => 'approved',
                            'hrd_approved_by'     => auth()->id(),
                            'hrd_approved_at'     => now(),
                            'status'              => 'approved',
                            'approved_by'         => auth()->id(),
                        ]);
                    }),
                Action::make('Reject HRD')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (ExtraHour $record) {
                        $user = auth()->user();
                        $isAdmin = $user->roles->contains(fn($role) => str_contains(strtolower($role->name), 'admin'));
                        $isHRD = $user->hasRole(['HRD', 'hrd']) || $isAdmin;
                        return $isHRD && $record->hrd_approval_status === 'pending';
                    })
                    ->action(function (ExtraHour $record) {
                        $record->update([
                            'hrd_approval_status' => 'rejected',
                            'hrd_approved_by'     => auth()->id(),
                            'hrd_approved_at'     => now(),
                            'status'              => 'rejected',
                        ]);
                    }),
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
