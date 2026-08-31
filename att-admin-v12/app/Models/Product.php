<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'principal_id',
        'company_id',
        'name',
        'sku_code',
        'barcode',
        'category',
        'brand',
        'price',
        'min_stock',
        'uom',
        'image_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'min_stock' => 'integer',
    ];

    public function getMinimalStockAttribute(): int
    {
        return (int) ($this->min_stock ?? 0);
    }

    public function getMinimumStockAttribute(): int
    {
        return (int) ($this->min_stock ?? 0);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reportTemplates(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ReportTemplate::class, 'report_template_product')->withTimestamps();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price ?? 0, 0, ',', '.');
    }
}
