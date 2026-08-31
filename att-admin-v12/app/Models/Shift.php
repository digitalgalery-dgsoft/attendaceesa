<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_cross_day' => 'boolean',
        'required_checkin' => 'boolean',
        'required_checkout' => 'boolean',
        'is_active' => 'boolean',
        'grace_checkin_minutes' => 'integer',
        'grace_checkout_minutes' => 'integer',
    ];

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
