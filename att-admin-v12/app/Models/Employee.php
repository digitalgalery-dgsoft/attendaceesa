<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Principal;
use App\Models\ReportTemplate;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes, Notifiable;
    
    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'tokens'];

    protected $casts = [
        'odoo_id' => 'integer',
    ];

    protected $appends = ['has_reporting_templates'];

    public function getHasReportingTemplatesAttribute(): bool
    {
        if (!$this->principal_id) {
            return false;
        }

        $principal = $this->principal;
        $allMatchingPrincipalIds = [$this->principal_id];
        if ($principal && !empty($principal->subdomain)) {
            $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
        }

        return ReportTemplate::where('is_active', true)
            ->where(function ($q) use ($allMatchingPrincipalIds) {
                $q->whereHas('principals', function ($pq) use ($allMatchingPrincipalIds) {
                    $pq->whereIn('principals.id', $allMatchingPrincipalIds);
                })
                ->orWhereIn('principal_id', $allMatchingPrincipalIds);
            })
            ->exists();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    public function getNikAttribute(): ?string
    {
        return $this->employee_no;
    }
}
