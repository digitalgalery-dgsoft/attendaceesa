<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['principal_id']) && empty($data['company_id'])) {
            $data['company_id'] = \App\Models\Principal::where('id', $data['principal_id'])->value('company_id');
        }
        return $data;
    }
}
