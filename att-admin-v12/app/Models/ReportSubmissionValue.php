<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSubmissionValue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value_number' => 'decimal:2',
        'value_json' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ReportSubmission::class, 'report_submission_id');
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(ReportFormField::class, 'report_form_field_id');
    }
}
