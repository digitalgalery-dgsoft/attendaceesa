<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'require_gps' => 'boolean',
        'require_signature' => 'boolean',
        'is_active' => 'boolean',
        'min_photos' => 'integer',
        'max_photos' => 'integer',
        'version' => 'integer',
        'report_days' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($template) {
            // Jika principal_id kosong tapi ada relasi principals di request
            if (empty($template->principal_id) && request()->has('principals')) {
                $principals = request()->input('principals', []);
                if (is_array($principals) && !empty($principals)) {
                    $template->principal_id = $principals[0];
                }
            }
        });
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function principals(): BelongsToMany
    {
        return $this->belongsToMany(Principal::class, 'report_template_principal')->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'report_template_product')->withTimestamps();
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'report_template_position')->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'report_template_employee')->withTimestamps();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ReportFormField::class)->orderBy('order_index');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ReportSubmission::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReportTemplateAssignment::class);
    }
}
