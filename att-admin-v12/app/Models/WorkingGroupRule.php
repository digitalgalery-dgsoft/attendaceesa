<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingGroupRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'working_group_id',
        'day_of_week',
        'shift_id',
        'late_tolerance',
        'routing_active',
        'store_assignment_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WorkingGroup::class, 'working_group_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function storeAssignment(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'store_assignment_id');
    }
}
