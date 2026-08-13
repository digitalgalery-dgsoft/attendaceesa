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
    public function fillCoordsFromGmaps(float $lat, float $lng, ?string $address = null): void
    {
        $this->data['latitude']  = $lat;
        $this->data['longitude'] = $lng;
        $this->data['location']  = ['lat' => $lat, 'lng' => $lng];
        
        if ($address) {
            $this->data['address'] = $address;
        }
        
        $this->dispatch('refreshMap');
    }
}
