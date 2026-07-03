<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Filament\Resources\Itineraries\ItineraryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CreateItinerary extends CreateRecord
{
    protected static string $resource = ItineraryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (($data['creation_type'] ?? 'single') === 'single') {
            return parent::handleRecordCreation($data);
        }

        // Whole Month Logic
        $employee = Employee::with('department')->find($data['employee_id']);
        $workingDays = $employee->department->working_days ?? ['1', '2', '3', '4', '5'];
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
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    ItineraryItem::create([
                        'itinerary_id' => $itinerary->id,
                        'work_location_id' => $item['work_location_id'],
                        'sequence' => $item['sequence'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            if (!$firstModel) {
                $firstModel = $itinerary;
            }
        }

        // Return first model so Filament can redirect to it (if empty return dummy)
        return $firstModel ?? Itinerary::make();
    }
}
