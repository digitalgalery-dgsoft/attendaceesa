<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'sub_type',
        'cuti_peraturan_type',
        'start_date',
        'end_date',
        'notes',
        'attachment_path',
        'status',
        'approved_by',
        'head_approval_status',
        'head_approved_by',
        'head_approved_at',
        'head_approval_notes',
        'hrd_approval_status',
        'hrd_approved_by',
        'hrd_approved_at',
        'hrd_approval_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
