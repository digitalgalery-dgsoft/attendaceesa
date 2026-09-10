<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'target_hk',
        'target_offtake_liter',
        'month_year',
    ];

    protected $casts = [
        'target_hk' => 'integer',
        'target_offtake_liter' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
