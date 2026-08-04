<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTarget extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'target_hk', 'month_year'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
