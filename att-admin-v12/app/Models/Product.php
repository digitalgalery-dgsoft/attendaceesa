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
        'pricing_matrix',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'min_stock' => 'integer',
        'pricing_matrix' => 'array',
    ];

    public function getDescriptionAttribute(?string $value): ?string
    {
        return self::formatDescriptionText($value);
    }

    public static function formatDescriptionText(?string $desc): ?string
    {
        if (empty($desc)) {
            return $desc;
        }

        $trimmed = trim($desc);
        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $data = json_decode($trimmed, true);
            if (is_array($data)) {
                $parts = [];
                $brand = $data['brand'] ?? '';
                $brandRmBase = $data['brand_rm_base'] ?? '';
                if ($brand && $brandRmBase && $brand !== $brandRmBase) {
                    $parts[] = "{$brand} ({$brandRmBase})";
                } elseif ($brandRmBase) {
                    $parts[] = $brandRmBase;
                } elseif ($brand) {
                    $parts[] = $brand;
                }

                $uom = $data['uom'] ?? 'Kg';
                $pkgs = $data['packaging_sizes'] ?? [];
                $pkgParts = [];
                if (!empty($pkgs['tin'])) {
                    $pkgParts[] = "Tin {$pkgs['tin']} {$uom}";
                }
                if (!empty($pkgs['galon'])) {
                    $pkgParts[] = "Galon {$pkgs['galon']} {$uom}";
                }
                if (!empty($pkgs['pail'])) {
                    $pkgParts[] = "Pail {$pkgs['pail']} {$uom}";
                }

                if (!empty($pkgParts)) {
                    $parts[] = "Kemasan: " . implode(', ', $pkgParts);
                }

                if (!empty($data['conversion_to_liter'])) {
                    $parts[] = "Konversi: {$data['conversion_to_liter']} Ltr/{$uom}";
                }

                return !empty($parts) ? implode('. ', $parts) : $desc;
            }
        }

        return $desc;
    }

    public function getMinimalStockAttribute(): int
    {
        return (int) ($this->min_stock ?? 0);
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            if ($product->principal_id) {
                try {
                    $templateIds = ReportTemplate::where('principal_id', $product->principal_id)
                        ->orWhereHas('principals', function ($q) use ($product) {
                            $q->where('principals.id', $product->principal_id);
                        })
                        ->where('code', 'NOT LIKE', '%DAILY-MAINTENANCE%')
                        ->pluck('id');

                    if ($templateIds->isNotEmpty()) {
                        $product->reportTemplates()->syncWithoutDetaching($templateIds);
                    }
                } catch (\Throwable $e) {
                    // Silently continue if table not yet migrated
                }
            }
        });
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
