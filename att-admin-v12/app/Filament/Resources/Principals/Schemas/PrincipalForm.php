<?php

namespace App\Filament\Resources\Principals\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PrincipalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama Prinsiple')
                    ->description('Data profil dan perusahaan prinsiple.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label('Company')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('code')
                                ->label('Kode Prinsiple')
                                ->placeholder('Contoh: PR-DULUX')
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama Prinsiple')
                                ->placeholder('Contoh: PT AKZONOBEL COATINGS INDONESIA (DULUX)')
                                ->required(),
                            TextInput::make('subdomain')
                                ->label('Subdomain Portal')
                                ->prefix('https://')
                                ->suffix('.appsend.my.id')
                                ->placeholder('dulux')
                                ->helperText('Subdomain portal login prinsiple (opsional, bisa dikosongkan jika tidak menggunakan portal subdomain).')
                                ->nullable(),
                        ]),
                        Textarea::make('description')
                            ->label('Deskripsi / Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Whitelabel Branding & Tampilan Portal')
                    ->description('Kustomisasi tampilan portal mandiri prinsiple ({subdomain}.appsend.my.id).')
                    ->schema([
                        TextInput::make('portal_title')
                            ->label('Judul Header Portal')
                            ->placeholder('Contoh: Portal Pelaporan & Monitoring Dulux')
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            ColorPicker::make('theme_color')
                                ->label('Warna Primer / Gradasi Awal (Hex)')
                                ->default('#0F52BA')
                                ->helperText('Warna utama identitas prinsiple.'),
                            ColorPicker::make('theme_color_secondary')
                                ->label('Warna Sekunder / Gradasi Akhir (Hex)')
                                ->placeholder('#1E88E5')
                                ->helperText('Warna kedua untuk efek gradasi 2 warna pada tombol, header & badge.'),
                        ]),
                        Grid::make(2)->schema([
                            FileUpload::make('logo_path')
                                ->label('Logo Prinsiple (Header/Login)')
                                ->image()
                                ->directory('principals/logos')
                                ->imageEditor(),
                            FileUpload::make('banner_path')
                                ->label('Banner Portal / Dashboard')
                                ->image()
                                ->directory('principals/banners')
                                ->imageEditor(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('custom_domain')
                                ->label('Domain Kustom Mandiri (Opsional)')
                                ->placeholder('portal.dulux.co.id')
                                ->helperText('Kosongkan jika menggunakan subdomain bawaan .appsend.my.id'),
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true)
                                ->helperText('Nonaktifkan jika prinsiple sudah tidak aktif bekerja sama.'),
                        ]),
                    ]),
            ]);
    }
}
