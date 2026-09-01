<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'principal_id',
        'code',
        'name',
        'level',
        'is_active',
        'allow_offline_mode',
        'distance_lock_override',
        'require_face_recognition',
    ];

    protected $attributes = [
        'require_face_recognition' => false,
    ];

    protected $casts = [
        'allow_offline_mode' => 'boolean',
        'distance_lock_override' => 'integer',
        'require_face_recognition' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($position) {
            if (empty($position->code)) {
                $position->code = 'POS-' . strtoupper(Str::random(5));
            }
            if ($position->require_face_recognition === null) {
                $position->require_face_recognition = false;
            }
        });

        static::saving(function ($position) {
            if (empty($position->code)) {
                $position->code = 'POS-' . strtoupper(Str::random(5));
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }
}
