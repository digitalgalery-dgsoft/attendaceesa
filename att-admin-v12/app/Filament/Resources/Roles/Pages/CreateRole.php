<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $record = static::getModel()::create($data);

        if (!empty($permissions)) {
            $record->syncPermissions($permissions);
        }

        return $record;
    }
}
