<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk & Kepemilikan Prinsiple')
                    ->description('Tentukan prinsiple pemilik dan data spesifikasi produk.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('principal_id')
                                ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                                ->label('Prinsiple Brand')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label('Company')
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            TextInput::make('name')
                                ->label('Nama Produk / SKU')
                                ->placeholder('Contoh: SoKlin Liquid Detergent Antibacterial 720ml')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('sku_code')
                                ->label('Kode SKU')
                                ->placeholder('Contoh: WNG-SKL-LIQ-720')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('barcode')
                                ->label('Barcode EAN / UPC')
                                ->placeholder('Contoh: 8998866102345')
                                ->nullable()
                                ->maxLength(100),

                            TextInput::make('brand')
                                ->label('Sub-Brand / Merek')
                                ->placeholder('Contoh: SoKlin, Daia, Anlene, MamaSuka, Dulux')
                                ->nullable()
                                ->maxLength(100),

                            TextInput::make('category')
                                ->label('Kategori Produk')
                                ->placeholder('Contoh: Food, Personal Care, Detergent, Dairy, Cat')
                                ->nullable()
                                ->maxLength(100),

                            Grid::make(3)->schema([
                                TextInput::make('price')
                                    ->label('Harga Standar (Rupiah)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->required(),

                                TextInput::make('min_stock')
                                    ->label('Stock Minimal Toko (Qty)')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Nilai acuan standar stok minimal pada form OOS / field minimal_stock.')
                                    ->required(),

                                TextInput::make('uom')
                                    ->label('Satuan Unit (UoM)')
                                    ->placeholder('Pcs / Sachet / Botol / Karton / Kaleng')
                                    ->default('Pcs')
                                    ->required(),
                            ]),
                        ]),

                        Textarea::make('description')
                            ->label('Deskripsi / Catatan Produk')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Media & Status')
                    ->schema([
                        Grid::make(2)->schema([
                            FileUpload::make('image_path')
                                ->label('Foto Kemasan / SKU')
                                ->image()
                                ->directory('products')
                                ->disk('public')
                                ->imagePreviewHeight('180')
                                ->nullable(),

                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->helperText('Hanya produk aktif yang akan dihitung dan dimonitor pada dashboard laporan.')
                                ->default(true),
                        ]),
                    ]),
            ]);
    }
}
