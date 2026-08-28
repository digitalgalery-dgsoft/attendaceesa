<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_log_id',
        'store_name',
        'oos_status',
        'oos_notes',
        'plano_status',
        'plano_notes',
        'promo_status',
        'promo_notes',
        'photo_oos',
        'photo_plano',
        'photo_promo',
        'notes',
        'report_date',
        'status',
        'location',
        'receipt_image',
        'ai_insights',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceLog::class);
    }
}
