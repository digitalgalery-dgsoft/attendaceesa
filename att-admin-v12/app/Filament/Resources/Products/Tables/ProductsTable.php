<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->square()
                    ->disk('public')
                    ->defaultImageUrl(url('/assets/default-product.png')),

                TextColumn::make('name')
                    ->label('Nama Produk / SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('sku_code')
                    ->label('SKU Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga Standar')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('uom')
                    ->label('Satuan')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('principal_id')
                    ->label('Filter Prinsiple')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->options(fn () => \App\Models\Product::whereNotNull('category')->distinct()->pluck('category', 'category')->toArray()),

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
            ]);
    }
}
