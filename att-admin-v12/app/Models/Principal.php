<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Principal extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'odoo_id' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
