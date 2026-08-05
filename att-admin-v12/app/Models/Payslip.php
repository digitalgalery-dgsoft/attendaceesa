<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month_year',
        'file_path',
        'is_published',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
