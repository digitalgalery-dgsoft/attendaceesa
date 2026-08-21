<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['principal_id']) && empty($data['company_id'])) {
            $data['company_id'] = \App\Models\Principal::where('id', $data['principal_id'])->value('company_id');
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
