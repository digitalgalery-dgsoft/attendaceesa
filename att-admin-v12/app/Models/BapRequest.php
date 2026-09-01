<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BapRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'principal_id',
        'company_id',
        'branch_id',
        'employee_schedule_id',
        'work_location_id',
        'attendance_id',
        'date',
        'checkin_time',
        'checkout_time',
        'type',
        'issue_category',
        'reason',
        'evidence_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date'        => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        if (empty($this->evidence_path)) {
            return null;
        }

        if (filter_var($this->evidence_path, FILTER_VALIDATE_URL) || str_starts_with($this->evidence_path, 'data:image')) {
            return $this->evidence_path;
        }

        return route('bap.evidence', ['id' => $this->id]);
    }

    public function getIssueCategoryLabelAttribute(): string
    {
        return match ($this->issue_category) {
            'app_error'    => 'Kendala Aplikasi (Error / Force Close)',
            'gps_network'  => 'Kendala Sinyal / Jaringan / GPS',
            'device_issue' => 'Kendala Handphone (Baterai Habis / Rusak)',
            'server_down'  => 'Server Error / Maintenance',
            'other'        => 'Kendala Operasional Lainnya',
            default        => ucfirst(str_replace('_', ' ', $this->issue_category ?? 'Lainnya')),
        };
    }
}
