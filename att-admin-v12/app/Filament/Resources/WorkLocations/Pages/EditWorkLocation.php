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
