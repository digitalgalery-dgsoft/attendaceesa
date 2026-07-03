<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'level',
        'is_active',
        'allow_offline_mode',
        'distance_lock_override',
    ];

    protected $casts = [
        'allow_offline_mode' => 'boolean',
        'distance_lock_override' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
