<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Forms\Components\PermissionMatrix;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Nama Role / Jabatan Akses')
                    ->placeholder('Contoh: Area Coordinator, HR Admin, Supervisor, Auditor')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),

                PermissionMatrix::make('permissions')
                    ->label('Pengaturan Akses Fitur & Menu')
                    ->helperText('Centang fitur-fitur yang diizinkan untuk diakses oleh role ini.')
                    ->columnSpanFull(),
            ]);
    }
}
