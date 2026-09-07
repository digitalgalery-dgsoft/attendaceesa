<?php

namespace App\Filament\Resources\WorkLocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkLocationsTable
{
    public static function canViewDuluxColumns(): bool
    {
        $user = auth()->user();
        if (!$user) return true;
        if ($user->isSuperAdmin()) return true;

        if (method_exists($user, 'getAccessiblePrincipalIds')) {
            $principalIds = $user->getAccessiblePrincipalIds();
            if (!empty($principalIds)) {
                return \App\Models\Principal::whereIn('id', $principalIds)
                    ->where(function ($q) {
                        $q->where('name', 'ilike', '%ici%')
                          ->orWhere('name', 'ilike', '%dulux%');
                    })
                    ->exists();
            }
        }
        return true;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Toko / Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode SAP')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => self::canViewDuluxColumns())
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('-'),
                TextColumn::make('category')
                    ->label('Kategori Store')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->visible(fn () => self::canViewDuluxColumns())
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('-'),
                TextColumn::make('machine_type')
                    ->label('Type Mesin')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => self::canViewDuluxColumns())
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('-'),
                TextColumn::make('machine_serial_no')
                    ->label('Nomor Mesin')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => self::canViewDuluxColumns())
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('-'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('region')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('area')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('channel')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('radius_meter')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('principal_id')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Area')
                    ->searchable()
                    ->preload(),
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
