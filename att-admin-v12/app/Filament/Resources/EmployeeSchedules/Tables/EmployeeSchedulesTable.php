<?php

namespace App\Filament\Resources\EmployeeSchedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->sortable(),
                TextColumn::make('workLocation.name')
                    ->label('Work Location')
                    ->sortable(),
                TextColumn::make('schedule_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('schedule_type')
                    ->badge(),
                TextColumn::make('planned_start_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('planned_end_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),
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
