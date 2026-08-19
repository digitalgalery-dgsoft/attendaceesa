<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'meet_in_at' => 'datetime',
        'meet_out_at' => 'datetime',
        'meet_in_lat' => 'float',
        'meet_in_lng' => 'float',
        'meet_out_lat' => 'float',
        'meet_out_lng' => 'float',
        'duration_seconds' => 'integer',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
