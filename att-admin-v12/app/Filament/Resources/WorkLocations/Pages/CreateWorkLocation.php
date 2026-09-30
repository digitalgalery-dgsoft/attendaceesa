<?php

namespace App\Filament\Resources\WorkLocations\Pages;

use App\Filament\Resources\WorkLocations\WorkLocationResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateWorkLocation extends CreateRecord
{
    protected static string $resource = WorkLocationResource::class;

    /**
     * Menerima event dari modal GMaps extractor (Alpine.js dispatch → Livewire).
     * Mengisi field latitude, longitude, dan memperbarui peta.
     */
    #[On('gmaps-coords-extracted')]
    public function fillCoordsFromGmaps(float $lat, float $lng): void
    {
        $this->data['latitude']  = \App\Models\WorkLocation::normalizeCoordinate($lat, 'lat');
        $this->data['longitude'] = \App\Models\WorkLocation::normalizeCoordinate($lng, 'lng');
        $this->data['location']  = ['lat' => $this->data['latitude'], 'lng' => $this->data['longitude']];
        $this->dispatch('refreshMap');
    }

    /**
     * Pastikan koordinat latitude dan longitude ternormalisasi sempurna sebelum insert DB.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['latitude'])) {
            $data['latitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['latitude'], 'lat');
        }
        if (isset($data['longitude'])) {
            $data['longitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['longitude'], 'lng');
        }
        return $data;
    }
}
