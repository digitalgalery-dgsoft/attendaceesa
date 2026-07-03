<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public $employee_id;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('employee_id', $data)) {
            $this->employee_id = $data['employee_id'];
            unset($data['employee_id']);
        }
        
        // Remove password if it's empty
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->employee_id)) {
            // Remove user_id from any other employee that might have it
            \App\Models\Employee::where('user_id', $this->record->id)->update(['user_id' => null]);
            
            if ($this->employee_id) {
                $employee = \App\Models\Employee::find($this->employee_id);
                if ($employee) {
                    $employee->user_id = $this->record->id;
                    $employee->save();
                }
            }
        }
    }
}
