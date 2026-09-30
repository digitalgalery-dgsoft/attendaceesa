<?php

namespace App\Filament\Resources\WorkLocations\Pages;

use App\Filament\Resources\WorkLocations\WorkLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditWorkLocation extends EditRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Menerima event dari modal GMaps extractor.
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
     * Pastikan koordinat latitude dan longitude ternormalisasi sempurna serta timezone akurat sebelum save DB.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['latitude'])) {
            $data['latitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['latitude'], 'lat');
        }
        if (isset($data['longitude'])) {
            $data['longitude'] = \App\Models\WorkLocation::normalizeCoordinate($data['longitude'], 'lng');
        }
        if (isset($data['latitude']) && isset($data['longitude'])) {
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
