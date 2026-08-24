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
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set, $get) {
                                    if ($operation === 'create' && empty($get('subdomain')) && !empty($state)) {
                                        $set('subdomain', Str::slug($state));
                                    }
                                })
                                ->required(),
                            TextInput::make('subdomain')
                                ->label('Subdomain Portal')
                                ->prefix('https://')
                                ->suffix('.appsend.my.id')
                                ->placeholder('dulux')
                                ->unique(ignoreRecord: true)
                                ->helperText('Subdomain eksklusif untuk portal login manajemen prinsiple.')
                                ->required(),
                        ]),
                        Textarea::make('description')
                            ->label('Deskripsi / Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Whitelabel Branding & Tampilan Portal')
                    ->description('Kustomisasi tampilan portal mandiri prinsiple ({subdomain}.appsend.my.id).')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('portal_title')
                                ->label('Judul Header Portal')
                                ->placeholder('Contoh: Portal Pelaporan & Monitoring Dulux'),
                            ColorPicker::make('theme_color')
                                ->label('Warna Identitas Tema (Hex)')
                                ->default('#0F52BA'),
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
