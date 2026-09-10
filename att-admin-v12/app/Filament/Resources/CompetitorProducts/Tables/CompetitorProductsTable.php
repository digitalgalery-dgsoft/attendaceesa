<?php

namespace App\Filament\Resources\CompetitorProducts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompetitorProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand')
                    ->label('Merk Kompetitor')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subbrand')
                    ->label('Nama Subbrand / Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category')
                    ->label('Kategori / Segmen')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('benchmark_price_tin')
                    ->label('Acuan Tin')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('benchmark_price_galon')
                    ->label('Acuan Galon')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),

                TextColumn::make('benchmark_price_pail')
                    ->label('Acuan Pail')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->label('Filter Merk')
                    ->options([
                        'JOTUN' => 'JOTUN',
                        'NIPPON PAINT' => 'NIPPON PAINT',
                        'AVIAN / NO DROP / LENKOTE' => 'AVIAN / NO DROP / LENKOTE',
                        'MOWILEX' => 'MOWILEX',
                        'PROPAN' => 'PROPAN',
                        'KANSAI / DANAPAINT' => 'KANSAI / DANAPAINT',
                        'PACIFIC PAINT' => 'PACIFIC PAINT',
                    ]),

                SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->options([
                        'Interior Premium' => 'Interior Premium',
                        'Interior Medium' => 'Interior Medium',
                        'Eksterior Premium' => 'Eksterior Premium',
                        'Eksterior Medium' => 'Eksterior Medium',
                        'Waterproofing' => 'Waterproofing',
                        'Wood & Metal' => 'Wood & Metal',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('brand', 'asc');
    }
}
