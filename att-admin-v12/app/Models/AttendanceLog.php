<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'employee_schedule_id',
        'itinerary_item_id',
        'log_type',
        'logged_at',
        'client_logged_at',
        'latitude',
        'longitude',
        'accuracy_meter',
        'altitude',
        'address_text',
        'photo_path',
        'note',
        'source',
        'validation_status',
        'validation_message',
        'is_inside_geofence',
        'distance_from_location_meter',
        'device_id',
        'ip_address',
        'user_agent',
        'metadata'
    ];
}
