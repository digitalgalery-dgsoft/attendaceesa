<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\Facades\Http;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class WorkLocationForm
{
    public static function isDuluxPrincipal(?string $principalId): bool
    {
        if (!$principalId) {
            $user = auth()->user();
            if ($user && method_exists($user, 'getAccessiblePrincipalIds')) {
                $accessibleIds = $user->getAccessiblePrincipalIds();
                if (!empty($accessibleIds)) {
                    return \App\Models\Principal::whereIn('id', $accessibleIds)
                        ->where(function ($q) {
                            $q->where('name', 'ilike', '%ici%')
                              ->orWhere('name', 'ilike', '%dulux%');
                        })->exists();
                }
            }
            return false;
        }

        $principal = \App\Models\Principal::find($principalId);
        if (!$principal) return false;

        $pName = strtolower($principal->name);
        return str_contains($pName, 'ici') || str_contains($pName, 'dulux');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('principal_id')
                    ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, ?string $state) {
                        if ($state) {
                            $principal = \App\Models\Principal::find($state);
                            if ($principal && $principal->company_id) {
                                $set('company_id', $principal->company_id);
                            }
                        }
                    }),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Area')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($set, ?string $state) {
                        if ($state) {
                            $branch = \App\Models\Branch::find($state);
                            if ($branch && $branch->region) {
                                $set('region', $branch->region);
                            }
                        }
                    }),
                TextInput::make('name')
                    ->label('Nama Lokasi / Toko')
                    ->required(),
                Select::make('type')
                    ->options([
                        'office' => 'Office',
                        'client' => 'Client',
                        'project' => 'Project',
                        'warehouse' => 'Warehouse',
                        'other' => 'Other',
                    ])
                    ->searchable()
                    ->default('client')
                    ->required(),

                \Filament\Schemas\Components\Section::make('Informasi Khusus Store Dulux (ICI PAINTS)')
                    ->description('Field khusus klasifikasi toko dan mesin tinting cat untuk prinsiple PT ICI PAINTS INDONESIA (Dulux).')
                    ->icon('heroicon-o-paint-brush')
                    ->visible(fn ($get, $record) => self::isDuluxPrincipal($get('principal_id') ?? $record?->principal_id))
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode SAP')
                            ->placeholder('Contoh: 422401')
                            ->maxLength(100),
                        TextInput::make('category')
                            ->label('Kategori Store')
                            ->placeholder('Contoh: SSO / MTI / Blue Store / Retail')
                            ->maxLength(100),
                        TextInput::make('machine_type')
                            ->label('Type Mesin')
                            ->placeholder('Contoh: D200, Discovery, X-Smart')
                            ->maxLength(100),
                        TextInput::make('machine_serial_no')
                            ->label('Nomor Mesin')
                            ->placeholder('Contoh: D10B0236, 670000001-2041875F')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Select::make('region')
                    ->options([
                        'Region 1' => 'Region 1',
                        'Region 2' => 'Region 2',
                        'Region 3' => 'Region 3',
                        'Region 4' => 'Region 4',
                        'Region 5' => 'Region 5',
                        'Region 6' => 'Region 6',
                        'Region 7' => 'Region 7',
                    ])
                    ->searchable()
                    ->live(),
                Select::make('sub_area')
                    ->options(function ($get) {
                        $region = $get('region');
                        if (!$region) return [];
                        
                        $file = 'G:\My File\Project APlikasi Absensi\New\tb_kota.csv';
                        if (!file_exists($file)) return [];
                        
                        $handle = fopen($file, "r");
                        fgetcsv($handle); // skip header
                        
                        $options = [];
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            if (count($data) >= 4 && $data[3] === $region) {
                                $options[$data[1]] = $data[1];
                            }
                        }
                        fclose($handle);
                        
                        asort($options);
                        return $options;
                    })
                    ->searchable(),
                TextInput::make('channel')
                    ->maxLength(255),
                TextInput::make('account')
                    ->maxLength(255),
                Select::make('timezone')
                    ->options([
                        'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                        'Asia/Makassar' => 'WITA (Asia/Makassar)',
                        'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                    ])
                    ->searchable()
                    ->default('Asia/Jakarta'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->searchable()
                    ->default('active')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
                Select::make('search_address')
                    ->label('Search Location')
                    ->columnSpanFull()
                    ->searchable()
                    ->suffixAction(
                        \Filament\Actions\Action::make('extract_gmaps_coords')
                            ->label('🗺️ Ekstrak Maps')
                            ->color('info')
                            ->modalHeading('Ekstrak Koordinat Maps')
                            ->modalDescription('Tempel link Google Maps untuk mendapatkan titik latitude dan longitude.')
                            ->modalWidth('md')
                            ->modalSubmitActionLabel('Gunakan Titik')
                            ->modalCancelActionLabel('Batal')
                            ->form([
                                \Filament\Forms\Components\TextInput::make('gmaps_url')
                                    ->label('Tautan Google Maps')
                                    ->placeholder('https://maps.google.com/... atau https://goo.gl/maps/...')
                                    ->helperText('Tempel URL Google Maps lengkap dari browser, atau link pendek (contoh: goo.gl). Sistem akan otomatis mencari titik koordinatnya.')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->action(function (array $data, $livewire) {
                                $url = $data['gmaps_url'] ?? '';
                                if (empty($url)) return;

                                $parsed = \App\Services\GoogleMapsService::parseCoordinates($url);

                                if ($parsed['success'] && !empty($parsed['latitude']) && !empty($parsed['longitude'])) {
                                    $lat = (float) $parsed['latitude'];
                                    $lng = (float) $parsed['longitude'];
                                    $livewire->dispatch('gmaps-coords-extracted', lat: $lat, lng: $lng);
                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Koordinat berhasil ditemukan')
                                        ->body("Latitude: {$lat} \nLongitude: {$lng}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Gagal mengekstrak koordinat')
                                        ->body($parsed['message'] ?? 'Format URL tidak dikenali. Coba gunakan format link yang berbeda atau pastikan link mengarah ke sebuah titik koordinat.')
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                }
                            })
                    )
                    ->getSearchResultsUsing(function (string $search): array {
                        if (blank($search)) {
                            return [];
                        }
                        
                        $response = Http::withHeaders([
                            'User-Agent' => 'AttendanceApp/1.0'
                        ])->get('https://nominatim.openstreetmap.org/search', [
                            'format' => 'json',
                            'q' => $search,
                            'limit' => 5,
                        ]);
                        
                        if ($response->successful()) {
                            return collect($response->json())
                                ->mapWithKeys(function ($item) {
                                    return [$item['lat'] . ',' . $item['lon'] => $item['display_name']];
                                })
                                ->toArray();
                        }
                        
                        return [];
                    })
                    ->getOptionLabelUsing(fn ($value): ?string => $value)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, \Livewire\Component $livewire) {
                        if (blank($state)) return;
                        
                        $coords = explode(',', $state);
                        if (count($coords) === 2) {
                            $lat = (float) $coords[0];
                            $lng = (float) $coords[1];
                            $set('latitude', $lat);
                            $set('longitude', $lng);
                            $set('location', ['lat' => $lat, 'lng' => $lng]);
                            
                            $livewire->dispatch('refreshMap');
                        }
                    })
                    ->dehydrated(false),
                TextInput::make('latitude')
                    ->required()
                    ->numeric()
                    ->readOnly(),
                TextInput::make('longitude')
                    ->required()
                    ->numeric()
                    ->readOnly(),
                Map::make('location')
                    ->label('Location Map')
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($set, ?array $state): void {
                        if (isset($state['lat']) && isset($state['lng'])) {
                            $set('latitude', $state['lat']);
                            $set('longitude', $state['lng']);
                        }
                    })
                    ->afterStateHydrated(function ($state, $record, $set): void {
                        if ($record && $record->latitude && $record->longitude) {
                            $set('location', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        }
                    })
                    ->live(onBlur: true)
                    ->showMarker()
                    ->markerColor("#22c55e")
                    ->showFullscreenControl()
                    ->showZoomControl()
                    ->draggable()
                    ->clickable(true)
                    ->defaultLocation(-7.2504, 112.7688)
                    ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                    ->zoom(15)
                    ->showMyLocationButton(),
                TextInput::make('radius_meter')
                    ->required()
                    ->numeric()
                    ->default(100),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
