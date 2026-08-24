<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Principal extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'odoo_id' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($principal) {
            if (empty($principal->subdomain) && !empty($principal->name)) {
                $slug = Str::slug($principal->name);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('subdomain', $slug)->exists()) {
                    $slug = "{$originalSlug}-{$count}";
                    $count++;
                }
                $principal->subdomain = $slug;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function workLocations(): HasMany
    {
        return $this->hasMany(WorkLocation::class);
    }

    public function reportTemplates(): BelongsToMany
    {
        return $this->belongsToMany(ReportTemplate::class, 'report_template_principal')->withTimestamps();
    }

    public function reportSubmissions(): HasMany
    {
        return $this->hasMany(ReportSubmission::class);
    }

    public function reportTemplateAssignments(): HasMany
    {
        return $this->hasMany(ReportTemplateAssignment::class);
    }

    public function getPortalUrlAttribute(): string
    {
        if ($this->custom_domain) {
            return "https://{$this->custom_domain}";
        }
        $sub = $this->subdomain ?: Str::slug($this->name);
        return "https://{$sub}.appsend.my.id";
    }
}
