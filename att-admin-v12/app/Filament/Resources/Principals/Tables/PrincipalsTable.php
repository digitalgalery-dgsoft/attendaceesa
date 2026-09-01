<?php

namespace App\Filament\Resources\Principals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrincipalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->logo_url)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=' . ltrim($record->theme_color ?: '0F52BA', '#') . '&color=fff')
                    ->extraImgAttributes(['class' => 'object-contain bg-white shadow-xs p-0.5']),
                TextColumn::make('name')
                    ->label('Nama Prinsiple')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('active_employees_count')
                    ->label('Employee Aktif')
                    ->counts('activeEmployees')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'danger')
                    ->icon('heroicon-m-users')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('subdomain')
                    ->label('Subdomain Portal')
                    ->formatStateUsing(fn ($record) => $record->portal_url)
                    ->url(fn ($record) => $record->portal_url, true)
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                ColorColumn::make('theme_color')
                    ->label('Warna')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
