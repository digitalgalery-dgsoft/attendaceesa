<?php

namespace App\Filament\Resources\Meetings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Meeting')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('meeting_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($record) => substr($record->start_time, 0, 5) . ($record->end_time ? ' - ' . substr($record->end_time, 0, 5) : '')),

                TextColumn::make('meeting_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'info',
                        'offline' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('location_name')
                    ->label('Lokasi / Link')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->meeting_type === 'online' ? $record->meeting_link : $record->location_name),

                TextColumn::make('participants_count')
                    ->counts('participants')
                    ->label('Peserta')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Hadir')
                    ->badge()
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('meeting_type')
                    ->label('Jenis Meeting')
                    ->options([
                        'offline' => 'Offline',
                        'online' => 'Online',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Laporan Hasil Meeting')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('meeting_date', 'desc');
    }
}
