<?php

namespace App\Filament\Resources\CompetitorProducts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompetitorProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Brand & Subbrand Kompetitor')
                    ->description('Data master merk dan subbrand kompetitor cat pembanding untuk laporan CBP.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('brand')
                                ->label('Nama Merk / Brand Kompetitor')
                                ->options([
                                    'JOTUN' => 'JOTUN',
                                    'NIPPON PAINT' => 'NIPPON PAINT',
                                    'AVIAN / NO DROP / LENKOTE' => 'AVIAN / NO DROP / LENKOTE',
                                    'MOWILEX' => 'MOWILEX',
                                    'PROPAN' => 'PROPAN',
                                    'KANSAI / DANAPAINT' => 'KANSAI / DANAPAINT',
                                    'PACIFIC PAINT' => 'PACIFIC PAINT',
                                    'MERK LAINNYA' => 'MERK LAINNYA',
                                ])
                                ->searchable()
                                ->required(),

                            Select::make('principal_id')
                                ->relationship('principal', 'name')
                                ->label('Terkait Prinsiple (Opsional)')
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            TextInput::make('subbrand')
                                ->label('Nama Subbrand / Produk Kompetitor')
                                ->placeholder('Contoh: Majestic True Beauty / Vinilex / Sunguard')
                                ->required()
                                ->maxLength(150),

                            Select::make('category')
                                ->label('Kategori / Segmen Produk')
                                ->options([
                                    'Interior Super Premium' => 'Interior Super Premium',
                                    'Interior Premium' => 'Interior Premium',
                                    'Interior Medium' => 'Interior Medium',
                                    'Interior Economy' => 'Interior Economy',
                                    'Eksterior Super Premium' => 'Eksterior Super Premium',
                                    'Eksterior Premium' => 'Eksterior Premium',
                                    'Eksterior Medium' => 'Eksterior Medium',
                                    'Waterproofing' => 'Waterproofing (Pelapis Anti Bocor)',
                                    'Wood & Metal' => 'Wood & Metal (Cat Kayu & Besi)',
                                    'Lainnya' => 'Lainnya',
                                ])
                                ->searchable()
                                ->nullable(),
                        ]),
                    ]),

                Section::make('Harga Acuan Pasar (Benchmark CBP)')
                    ->description('Estimasi harga jual pasar ke konsumen untuk tiap ukuran kemasan.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('benchmark_price_tin')
                                ->label('Harga Acuan Tin / 1L / 1Kg (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->nullable(),

                            TextInput::make('benchmark_price_galon')
                                ->label('Harga Acuan Galon / 2.5L / 4-5Kg (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->nullable(),

                            TextInput::make('benchmark_price_pail')
                                ->label('Harga Acuan Pail / 20L / 25Kg (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('order_index')
                                ->label('Urutan Tampilan')
                                ->numeric()
                                ->default(0),

                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true)
                                ->helperText('Jika nonaktif, produk tidak akan muncul di dropdown pelaporan mobile/portal.'),
                        ]),
                    ]),
            ]);
    }
}
