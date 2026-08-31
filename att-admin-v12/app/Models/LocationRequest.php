<?php

namespace App\Models;

use App\Services\GoogleMapsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meter' => 'integer',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($request) {
            // Auto parse coordinates from maps_url if latitude/longitude empty
            if (!empty($request->maps_url) && (empty($request->latitude) || empty($request->longitude))) {
                $parsed = GoogleMapsService::parseCoordinates($request->maps_url);
                if ($parsed['success'] && !empty($parsed['latitude']) && !empty($parsed['longitude'])) {
                    $request->latitude = $parsed['latitude'];
                    $request->longitude = $parsed['longitude'];
                }
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
