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

    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('is_active', true)->whereNull('deleted_at');
    }

    public function syncActiveStatus(): bool
    {
        $hasActive = $this->activeEmployees()->exists();
        if ($this->is_active !== $hasActive) {
            $this->update(['is_active' => $hasActive]);
            return true;
        }
        return false;
    }

    public static function syncAllActiveStatuses(): void
    {
        static::whereDoesntHave('activeEmployees')->where('is_active', true)->update(['is_active' => false]);
        static::whereHas('activeEmployees')->where('is_active', false)->update(['is_active' => true]);
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getPortalUrlAttribute(): string
    {
        if ($this->custom_domain) {
            return "https://{$this->custom_domain}";
        }
        $sub = $this->subdomain ?: Str::slug($this->name);
        return "https://{$sub}.appsend.my.id?p={$this->id}";
    }

    public function getThemeGradientAttribute(): string
    {
        $primary = $this->theme_color ?: '#0F52BA';
        $secondary = $this->theme_color_secondary ?: $primary;
        return "linear-gradient(135deg, {$primary} 0%, {$secondary} 100%)";
    }

    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->logo_path;

        // Inherit from sibling under the same subdomain if not directly set
        if (!$path && !empty($this->subdomain)) {
            $sibling = static::where('subdomain', $this->subdomain)
                ->whereNotNull('logo_path')
                ->where('logo_path', '!=', '')
                ->first();
            if ($sibling) {
                $path = $sibling->logo_path;
            }
        }

        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) || str_starts_with($path, 'data:image')) {
            return $path;
        }

        // Return dedicated logo stream route
        return route('portal.logo', ['id' => $this->id]);
    }

    public function getBannerUrlAttribute(): ?string
    {
        $path = $this->banner_path;

        // Inherit from sibling under the same subdomain if not directly set
        if (!$path && !empty($this->subdomain)) {
            $sibling = static::where('subdomain', $this->subdomain)
                ->whereNotNull('banner_path')
                ->where('banner_path', '!=', '')
                ->first();
            if ($sibling) {
                $path = $sibling->banner_path;
            }
        }

        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) || str_starts_with($path, 'data:image')) {
            return $path;
        }

        return route('portal.banner', ['id' => $this->id]);
    }
}
