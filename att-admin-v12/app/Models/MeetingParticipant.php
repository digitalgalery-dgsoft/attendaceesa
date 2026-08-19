<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
