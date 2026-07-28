<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = ['id'];
    
    protected $casts = [
        'settings' => 'array',
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'company_department');
    }

    public function principals()
    {
        return $this->hasMany(Principal::class);
    }
}
