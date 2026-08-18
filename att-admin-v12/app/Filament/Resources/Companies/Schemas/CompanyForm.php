<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Company Info')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('code')
                            ->default(fn () => 'COM-' . strtoupper(\Illuminate\Support\Str::random(5)))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->required(),
                    ]),
                \Filament\Schemas\Components\Section::make('Odoo Integration')
                    ->description('Konfigurasi koneksi ke Odoo ERP untuk sinkronisasi data per perusahaan.')
                    ->schema([
                        TextInput::make('odoo_url')
                            ->label('Odoo URL')
                            ->url()
                            ->placeholder('https://your-odoo-instance.com'),
                        TextInput::make('odoo_db')
                            ->label('Odoo Database Name'),
                        TextInput::make('odoo_username')
                            ->label('Odoo Username / Email'),
                        TextInput::make('odoo_api_key')
                            ->label('Odoo API Key / Password')
                            ->password()
                            ->revealable(),
                    ])->columns(2),
            ]);
    }
}
