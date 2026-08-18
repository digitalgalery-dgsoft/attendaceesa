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
        'target_type',
        'target_qty',
        'actual_qty',
        'target_value',
        'actual_value',
        'deadline',
        'notes',
        'photo_path',
        'status',
        'principal_id',
        'met_with',
        'position',
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

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }
}
