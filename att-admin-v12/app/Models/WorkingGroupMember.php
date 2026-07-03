<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'working_group_id',
        'employee_id',
        'master_shift_id',
        'late_tolerance',
        'first_visit_store_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WorkingGroup::class, 'working_group_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'master_shift_id');
    }

    public function firstVisitStore(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'first_visit_store_id');
    }
}
