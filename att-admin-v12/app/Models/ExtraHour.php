<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'notes',
        'cross_day',
        'status',
        'approved_by',
        'head_approval_status',
        'head_approved_by',
        'head_approved_at',
        'hrd_approval_status',
        'hrd_approved_by',
        'hrd_approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'cross_day' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function headApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function hrdApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrd_approved_by');
    }
}
