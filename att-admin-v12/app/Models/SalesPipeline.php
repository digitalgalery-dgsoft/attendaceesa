<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'employee_id',
        'lead_name',
        'lead_company',
        'contact_info',
        'stage',
        'expected_revenue',
        'probability',
        'expected_close_date',
        'notes',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'expected_revenue' => 'decimal:2',
        'probability' => 'decimal:2',
    ];

    public function salesReport(): BelongsTo
    {
        return $this->belongsTo(SalesReport::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
