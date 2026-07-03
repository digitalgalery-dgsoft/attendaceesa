<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_item_id',
        'employee_id',
        'issue',
        'action_taken',
        'target',
        'actual',
        'deadline',
        'notes',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function itineraryItem(): BelongsTo
    {
        return $this->belongsTo(ItineraryItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
