<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    use HasFactory;



    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($location) {
            if (empty($location->code)) {
                $location->code = 'LOC-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    protected $casts = [
        'machines' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get effective GPS radius in meters for a specific employee.
     * Priority: Position radius (distance_lock_override) -> WorkLocation radius (radius_meter) -> Default 100m.
     */
    public function getEffectiveRadiusForEmployee(?Employee $employee = null): int
    {
        if ($employee && $employee->position && !empty($employee->position->distance_lock_override) && (int) $employee->position->distance_lock_override > 0) {
            return (int) $employee->position->distance_lock_override;
        }

        return (int) ($this->radius_meter ?? 100);
    }

    /**
     * Get normalized list of tinting machines for this location.
     * Combines 'machines' JSON array and fallback to scalar machine_type/machine_serial_no.
     */
    public function getNormalizedMachinesAttribute(): array
    {
        $result = [];
        $rawMachines = $this->machines;

        if (is_array($rawMachines) && !empty($rawMachines)) {
            foreach ($rawMachines as $m) {
                if (is_array($m)) {
                    $mType = trim($m['machine_type'] ?? $m['type'] ?? '');
                    $mSerial = trim($m['machine_serial_no'] ?? $m['serial_no'] ?? '');
                    if ($mType !== '' || $mSerial !== '') {
                        $result[] = [
                            'machine_type' => $mType ?: 'Mesin Tinting',
                            'machine_serial_no' => $mSerial,
                        ];
                    }
                }
            }
        }

        if (empty($result) && (!empty($this->machine_type) || !empty($this->machine_serial_no))) {
            $result[] = [
                'machine_type' => trim((string)$this->machine_type) ?: 'Mesin Tinting',
                'machine_serial_no' => trim((string)$this->machine_serial_no),
            ];
        }

        return $result;
    }
}
