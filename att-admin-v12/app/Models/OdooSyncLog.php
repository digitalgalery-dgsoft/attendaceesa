<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
        'new_count' => 'integer',
        'update_count' => 'integer',
        'resign_count' => 'integer',
        'total_employee_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
