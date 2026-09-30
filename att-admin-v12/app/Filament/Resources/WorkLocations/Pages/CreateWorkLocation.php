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
        $normLat = \App\Models\WorkLocation::normalizeCoordinate($lat, 'lat');
        $normLng = \App\Models\WorkLocation::normalizeCoordinate($lng, 'lng');
        $this->data['latitude']  = $normLat;
        $this->data['longitude'] = $normLng;
        $this->data['location']  = ['lat' => $normLat, 'lng' => $normLng];
        $this->data['timezone']  = \App\Models\WorkLocation::determineTimezone($normLat, $normLng, $this->data['sub_area'] ?? null);
        $this->dispatch('refreshMap');
    }

    /**
     * Pastikan koordinat latitude dan longitude ternormalisasi sempurna serta timezone akurat sebelum insert DB.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['latitude'])) {
            $data['latitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['latitude'], 'lat');
        }
        if (isset($data['longitude'])) {
            $data['longitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['longitude'], 'lng');
        }
        if (isset($data['latitude']) && isset($data['longitude'])) {
            // Jika timezone belum diisi atau masih default Asia/Jakarta sementara lokasi ada di WITA/WIT
            $detectedTz = \App\Models\WorkLocation::determineTimezone(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $data['sub_area'] ?? null
            );
            if (empty($data['timezone']) || ($data['timezone'] === 'Asia/Jakarta' && $detectedTz !== 'Asia/Jakarta')) {
                $data['timezone'] = $detectedTz;
            }
        }
        return $data;
    }
}
