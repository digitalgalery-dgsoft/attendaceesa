<?php

namespace App\Filament\Imports;

use App\Models\Principal;
use App\Models\Shift;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class ShiftImporter extends Importer
{
    protected static ?string $model = Shift::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('principal')
                ->relationship(resolveUsing: 'name')
                ->label('Prinsiple'),
            ImportColumn::make('name')
                ->label('Nama Shift')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('code')
                ->label('Kode Shift')
                ->rules(['nullable', 'max:50']),
            ImportColumn::make('start_time')
                ->label('Jam Masuk')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('end_time')
                ->label('Jam Pulang')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('break_start_time')
                ->label('Istirahat Mulai'),
            ImportColumn::make('break_end_time')
                ->label('Istirahat Selesai'),
            ImportColumn::make('grace_checkin_minutes')
                ->label('Toleransi Masuk (Menit)')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('grace_checkout_minutes')
                ->label('Toleransi Pulang (Menit)')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_cross_day')
                ->label('Lintas Hari')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('required_checkin')
                ->label('Wajib Checkin')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('required_checkout')
                ->label('Wajib Checkout')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('is_active')
                ->label('Aktif')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Shift
    {
        if (!empty($this->data['code'])) {
            $existing = Shift::where('code', $this->data['code'])->first();
            if ($existing) {
                return $existing;
            }
        }

        if (!empty($this->data['name'])) {
            $query = Shift::where('name', $this->data['name']);
            if (!empty($this->data['principal'])) {
                $pId = Principal::where('name', $this->data['principal'])->value('id');
                if ($pId) {
                    $query->where('principal_id', $pId);
                }
            }
            $existing = $query->first();
            if ($existing) {
                return $existing;
            }
        }

        return new Shift();
    }

    protected function beforeSave(): void
    {
        if (empty($this->record->code)) {
            do {
                $code = 'SHF-' . strtoupper(Str::random(5));
            } while (Shift::where('code', $code)->exists());
            $this->record->code = $code;
        }

        if ($this->record->grace_checkin_minutes === null) {
            $this->record->grace_checkin_minutes = 0;
        }

        if ($this->record->grace_checkout_minutes === null) {
            $this->record->grace_checkout_minutes = 0;
        }

        if ($this->record->is_cross_day === null) {
            $this->record->is_cross_day = ($this->record->end_time && $this->record->start_time && $this->record->end_time < $this->record->start_time);
        }

        if ($this->record->required_checkin === null) {
            $this->record->required_checkin = true;
        }

        if ($this->record->required_checkout === null) {
            $this->record->required_checkout = true;
        }

        if ($this->record->is_active === null) {
            $this->record->is_active = true;
        }

        if (empty($this->record->principal_id)) {
            $this->record->principal_id = Principal::where('is_active', true)->value('id') ?? Principal::value('id');
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import shift telah selesai dan ' . Number::format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimpor.';
        }

        return $body;
    }
}
