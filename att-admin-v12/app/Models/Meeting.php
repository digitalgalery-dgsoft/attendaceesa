<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'meeting_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meter' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'meeting_participants')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }
}
