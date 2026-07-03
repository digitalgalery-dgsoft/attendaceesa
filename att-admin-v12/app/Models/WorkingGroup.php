<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region',
        'area',
        'sub_area',
        'data_applied_date',
    ];

    protected $casts = [
        'data_applied_date' => 'date',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(WorkingGroupMember::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WorkingGroupRule::class);
    }
}
