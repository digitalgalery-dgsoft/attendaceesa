<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['password'])) {
            $data['password'] = Hash::make('123456');
        }
        if (empty($data['company_id'])) {
            if (!empty($data['principal_id'])) {
                $principal = \App\Models\Principal::find($data['principal_id']);
                $data['company_id'] = ($principal && $principal->company_id) ? $principal->company_id : (\App\Models\Company::first() ? \App\Models\Company::first()->id : 1);
            } else {
                $firstCompany = \App\Models\Company::first();
                $data['company_id'] = $firstCompany ? $firstCompany->id : 1;
            }
        }
        return $data;
    }
}
