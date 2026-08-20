<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'work_location_id',
        'sequence',
        'is_checkin_location',
        'notes',
        'principal_id',
        'visit_type',
        'meeting_type',
        'agenda',
    ];

    protected $casts = [
        'is_checkin_location' => 'boolean',
    ];

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }
}
