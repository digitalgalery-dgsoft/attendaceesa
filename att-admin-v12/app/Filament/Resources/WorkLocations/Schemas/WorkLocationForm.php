<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
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
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
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
                    ->required(),
                TextInput::make('region')
                    ->maxLength(255),
                TextInput::make('area')
                    ->maxLength(255),
                TextInput::make('sub_area')
                    ->maxLength(255),
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
                    ->default('Asia/Jakarta'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
                Select::make('search_address')
                    ->label('Search Location')
                    ->columnSpanFull()
                    ->searchable()
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
