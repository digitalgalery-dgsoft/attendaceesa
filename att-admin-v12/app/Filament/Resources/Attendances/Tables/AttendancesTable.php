<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employee_schedule_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('checkin_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('checkout_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('checkin_log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checkout_log_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('work_duration_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('early_leave_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_minutes')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_manual_correction')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
