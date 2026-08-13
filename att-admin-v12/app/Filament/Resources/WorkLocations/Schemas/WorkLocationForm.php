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
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('principal_id')
                    ->options(function ($get) {
                        $companyId = $get('company_id');
                        if (!$companyId) {
                            return \App\Models\Principal::pluck('name', 'id');
                        }
                        return \App\Models\Principal::where('company_id', $companyId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->label('Prinsiple'),
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
                    ->required(),

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
                                $lat = null;
                                $lng = null;

                                if (empty($url)) return;

                                // Expand URL if it's a short link (goo.gl or maps.app.goo.gl)
                                if (str_contains($url, 'goo.gl') || str_contains($url, 'maps.app.goo.gl')) {
                                    try {
                                        // Attempt to follow redirect to get the full URL
                                        $response = \Illuminate\Support\Facades\Http::get($url);
                                        $url = $response->effectiveUri() ?? $url;
                                    } catch (\Exception $e) {
                                        // Fallback to the original URL if request fails
                                    }
                                }

                                $patterns = [
                                    '/!3d(-?\d+\.?\d*).*?!4d(-?\d+\.?\d*)/',
                                    '/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/',
                                    '/place\/[^@]*@(-?\d+\.?\d*),(-?\d+\.?\d*)/',
                                    '/mlat=(-?\d+\.?\d*).*?mlon=(-?\d+\.?\d*)/',
                                    '/ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/',
                                    '/@(-?\d+\.?\d*),(-?\d+\.?\d*)/',
                                ];
                                
                                foreach ($patterns as $pattern) {
                                    if (preg_match($pattern, (string)$url, $m)) {
                                        $lat = $m[1];
                                        $lng = $m[2];
                                        break;
                                    }
                                }

                                if ($lat && $lng) {
                                    $livewire->dispatch('gmaps-coords-extracted', lat: (float)$lat, lng: (float)$lng);
                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Koordinat berhasil ditemukan')
                                        ->body("Latitude: {$lat} \nLongitude: {$lng}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Gagal mengekstrak koordinat')
                                        ->body('Format URL tidak dikenali. Coba gunakan format link yang berbeda atau pastikan link mengarah ke sebuah titik koordinat.')
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
