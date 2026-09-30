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
        $this->data['latitude']  = \App\Models\WorkLocation::normalizeCoordinate($lat, 'lat');
        $this->data['longitude'] = \App\Models\WorkLocation::normalizeCoordinate($lng, 'lng');
        $this->data['location']  = ['lat' => $this->data['latitude'], 'lng' => $this->data['longitude']];
        $this->dispatch('refreshMap');
    }

    /**
     * Pastikan koordinat latitude dan longitude ternormalisasi sempurna sebelum save DB.
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
