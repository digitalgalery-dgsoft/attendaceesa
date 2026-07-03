<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'employee_id',
        'employee_schedule_id',
        'attendance_date',
        'status',
        'checkin_at',
        'checkout_at',
        'checkin_log_id',
        'checkout_log_id',
        'work_duration_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'is_manual_correction',
        'correction_note'
    ];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }
}
