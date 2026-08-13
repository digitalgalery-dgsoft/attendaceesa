<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveQuota extends Model
{
    protected $fillable = [
        'employee_id',
        'year',
        'total_quota',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
