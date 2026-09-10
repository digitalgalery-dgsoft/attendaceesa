<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetitorProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'competitor_products';

    protected $fillable = [
        'principal_id',
        'brand',
        'subbrand',
        'category',
        'packaging_sizes',
        'benchmark_price_tin',
        'benchmark_price_galon',
        'benchmark_price_pail',
        'is_active',
        'order_index',
    ];

    protected $casts = [
        'packaging_sizes' => 'array',
        'benchmark_price_tin' => 'decimal:2',
        'benchmark_price_galon' => 'decimal:2',
        'benchmark_price_pail' => 'decimal:2',
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }
}
