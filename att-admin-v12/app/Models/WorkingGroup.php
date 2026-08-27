<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class WorkingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region',
        'area',
        'branch_id',
        'principal_id',
        'sub_area',
        'data_applied_date',
        'default_shift_id',
        'default_late_tolerance',
        'default_work_location_id',
        'created_by',
    ];

    protected $casts = [
        'data_applied_date' => 'date',
        'default_late_tolerance' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function defaultShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'default_shift_id');
    }

    public function defaultWorkLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'default_work_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkingGroupMember::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WorkingGroupRule::class);
    }

    /**
     * Generate employee schedules for all members of this working group throughout the given date range.
     * Defaults to Date Applied through End of the Running Year.
     */
    public function generateSchedules(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $start = $startDate ? $startDate->copy()->startOfDay() : ($this->data_applied_date ? Carbon::parse($this->data_applied_date)->startOfDay() : Carbon::today()->startOfDay());
        $end = $endDate ? $endDate->copy()->endOfDay() : $start->copy()->endOfYear()->endOfDay();

        $members = $this->members()->with(['employee', 'shift', 'firstVisitStore'])->get();
        if ($members->isEmpty()) {
            return 0;
        }

        $rules = $this->rules()->with(['shift', 'storeAssignment'])->get()->keyBy('day_of_week');

        // Preload default shift if available
        $defaultShift = $this->default_shift_id ? Shift::find($this->default_shift_id) : null;
        $defaultLocationId = $this->default_work_location_id;

        $totalGenerated = 0;
        $authId = Auth::id() ?? $this->created_by;

        foreach ($members as $member) {
            $currentDate = $start->copy();
            $employeeId = $member->employee_id;

            while ($currentDate->lte($end)) {
                $dayName = $currentDate->format('l'); // Monday, Tuesday, ...
                $rule = $rules->get($dayName);

                $isWorkDay = false;
                $shiftToUse = null;
                $locationIdToUse = null;

                if ($rule && $rule->is_active) {
                    $isWorkDay = true;
                    if ($rule->has_custom_option) {
                        $shiftToUse = $rule->shift ?: $defaultShift;
                        $locationIdToUse = $rule->store_assignment_id ?: $defaultLocationId;
                    } else {
                        $shiftToUse = $defaultShift ?: $rule->shift;
                        $locationIdToUse = $defaultLocationId ?: $rule->store_assignment_id;
                    }
                } elseif (!$rule) {
                    // Fallback: If no rule record, Mon-Fri is workday
                    if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                        $isWorkDay = true;
                        $shiftToUse = $defaultShift;
                        $locationIdToUse = $defaultLocationId;
                    }
                }

                if ($isWorkDay) {
                    $scheduleType = 'workday';
                    $shiftId = $shiftToUse ? $shiftToUse->id : null;
                    $plannedStart = null;
                    $plannedEnd = null;

                    if ($shiftToUse && $shiftToUse->start_time && $shiftToUse->end_time) {
                        $plannedStart = Carbon::parse($currentDate->toDateString() . ' ' . $shiftToUse->start_time);
                        $plannedEnd = Carbon::parse($currentDate->toDateString() . ' ' . $shiftToUse->end_time);

                        if ($shiftToUse->is_cross_day ?? false) {
                            $plannedEnd->addDay();
                        } elseif ($plannedEnd->lt($plannedStart)) {
                            $plannedEnd->addDay();
                        }
                    }

                    EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'schedule_date' => $currentDate->toDateString(),
                        ],
                        [
                            'shift_id' => $shiftId,
                            'work_location_id' => $locationIdToUse,
                            'schedule_type' => $scheduleType,
                            'planned_start_at' => $plannedStart,
                            'planned_end_at' => $plannedEnd,
                            'created_by' => $authId,
                        ]
                    );
                } else {
                    EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'schedule_date' => $currentDate->toDateString(),
                        ],
                        [
                            'shift_id' => null,
                            'work_location_id' => null,
                            'schedule_type' => 'dayoff',
                            'planned_start_at' => null,
                            'planned_end_at' => null,
                            'created_by' => $authId,
                        ]
                    );
                }

                $totalGenerated++;
                $currentDate->addDay();
            }
        }

        return $totalGenerated;
    }
}
