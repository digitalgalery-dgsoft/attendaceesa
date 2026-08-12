<?php

namespace App\Filament\Resources\BlastInfoResource\Pages;

use App\Filament\Resources\BlastInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBlastInfo extends CreateRecord
{
    protected static string $resource = BlastInfoResource::class;

    protected function afterCreate(): void
    {
        $blastInfo = $this->record;
        $firebase = new \App\Services\FirebaseService();

        $query = \App\Models\Employee::whereNotNull('fcm_token')->where('is_active', true);
        
        if ($blastInfo->target_type === 'department') {
            $query->where('department_id', $blastInfo->department_id);
        }
        
        $tokens = $query->pluck('fcm_token')->toArray();

        if (!empty($tokens)) {
            $firebase->sendNotification(
                $tokens,
                $blastInfo->title,
                $blastInfo->content
            );
        }
    }
}
