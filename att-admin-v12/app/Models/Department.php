<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'principal_id',
        'name',
        'code',
        'parent_id',
        'is_active',
        'working_days',
        'has_sales_reporting',
        'cutoff_start_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'working_days' => 'array',
        'has_sales_reporting' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($department) {
            if (empty($department->code)) {
                $department->code = 'DEP-' . strtoupper(Str::random(5));
            }
        });

        static::saving(function ($department) {
            if (empty($department->code)) {
                $department->code = 'DEP-' . strtoupper(Str::random(5));
            }
        });
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_department');
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }
}
