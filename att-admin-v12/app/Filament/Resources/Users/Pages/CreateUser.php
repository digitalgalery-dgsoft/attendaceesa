<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public $employee_id;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['employee_id'])) {
            $this->employee_id = $data['employee_id'];
            unset($data['employee_id']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->employee_id) {
            $employee = \App\Models\Employee::find($this->employee_id);
            if ($employee) {
                // Remove user_id from any other employee that might have it
                \App\Models\Employee::where('user_id', $this->record->id)->update(['user_id' => null]);
                
                $employee->user_id = $this->record->id;
                $employee->save();
            }
        }
    }
}
