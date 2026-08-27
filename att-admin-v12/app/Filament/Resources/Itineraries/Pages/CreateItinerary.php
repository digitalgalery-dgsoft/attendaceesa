<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Filament\Resources\Itineraries\ItineraryResource;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateItinerary extends CreateRecord
{
    protected static string $resource = ItineraryResource::class;

    public function mount(): void
    {
        parent::mount();

        $dateParam = request()->query('date');
        if ($dateParam) {
            $this->form->fill([
                'date' => $dateParam,
                'creation_type' => 'single',
                'status' => 'approved',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        if (($data['creation_type'] ?? 'single') === 'single') {
            return parent::handleRecordCreation($data);
        }

        // Whole Month Logic
        $employee = Employee::with('department')->find($data['employee_id']);
        $workingDays = $employee?->department?->working_days ?? ['1', '2', '3', '4', '5'];
        $year = (int) $data['year'];
        $month = (int) $data['month'];
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $firstModel = null;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);

            if (!in_array((string) $currentDate->dayOfWeek, $workingDays)) {
                continue;
            }

            if (in_array($currentDate->format('Y-m-d'), $holidays)) {
                continue;
            }

            $itinerary = Itinerary::create([
                'employee_id' => $employee->id,
                'date' => $currentDate->format('Y-m-d'),
                'status' => $data['status'] ?? 'approved',
                'is_strict_routing' => (bool)($data['is_strict_routing'] ?? false),
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    ItineraryItem::create([
                        'itinerary_id' => $itinerary->id,
                        'work_location_id' => $item['work_location_id'],
                        'sequence' => $item['sequence'] ?? 1,
                        'principal_id' => $item['principal_id'] ?? $employee->principal_id,
                        'visit_type' => $item['visit_type'] ?? 'store',
                        'is_checkin_location' => (bool)($item['is_checkin_location'] ?? false),
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            if (!$firstModel) {
                $firstModel = $itinerary;
            }
        }

        return $firstModel ?? Itinerary::make();
    }
}
