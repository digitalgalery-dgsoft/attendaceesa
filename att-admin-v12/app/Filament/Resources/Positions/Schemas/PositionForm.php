<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('principal_id')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'POS-' . strtoupper(Str::random(5)))
                    ->afterStateHydrated(fn (TextInput $component, ?string $state) => empty($state) ? $component->state('POS-' . strtoupper(Str::random(5))) : null)
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('distance_lock_override')
                    ->label('Radius GPS / Geofence (Meter)')
                    ->numeric()
                    ->suffix('Meter')
                    ->placeholder('Ikuti Work Location (Default)')
                    ->helperText('Batas toleransi radius presensi/laporan untuk jabatan ini. Kosongkan jika ingin mengikuti pengaturan radius dari masing-masing Work Location.'),
                Toggle::make('require_face_recognition')
                    ->label('Wajib Face Recognition (Liveness AI)')
                    ->default(true)
                    ->helperText('Jika aktif, karyawan dengan jabatan ini wajib verifikasi deteksi wajah & liveness AI saat presensi. Jika dinonaktifkan, deteksi wajah bersifat opsional/dapat langsung foto.'),
                Toggle::make('allow_offline_mode')
                    ->label('Allow Offline Mode'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
