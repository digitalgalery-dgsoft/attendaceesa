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

        static::saving(function ($location) {
            if (isset($location->latitude)) {
                $location->latitude = static::normalizeCoordinate($location->latitude, 'lat');
            }
            if (isset($location->longitude)) {
                $location->longitude = static::normalizeCoordinate($location->longitude, 'lng');
            }
        });
    }

    /**
     * Set normalized latitude attribute.
     */
    public function setLatitudeAttribute($value): void
    {
        $this->attributes['latitude'] = static::normalizeCoordinate($value, 'lat');
    }

    /**
     * Set normalized longitude attribute.
     */
    public function setLongitudeAttribute($value): void
    {
        $this->attributes['longitude'] = static::normalizeCoordinate($value, 'lng');
    }

    /**
     * Robust coordinate normalizer.
     * Handles:
     * - Coordinates missing decimal dots (e.g. -7306806 -> -7.306806, 1126566062 -> 112.6566062)
     * - Comma as decimal separator (e.g. -7,306806 -> -7.306806)
     * - Out of range values
     */
    public static function normalizeCoordinate($value, string $type = 'lat'): ?float
    {
        if ($value === null || $value === '') return null;
        $str = trim((string) $value);

        // If string has only 1 comma and no dot: e.g. "-7,306806"
        if (substr_count($str, ',') === 1 && !str_contains($str, '.')) {
            $str = str_replace(',', '.', $str);
        }

        $clean = preg_replace('/[^0-9.-]/', '', $str);
        if (!is_numeric($clean)) return null;

        $num = (float) $clean;
        $max = ($type === 'lat') ? 90.0 : 180.0;

        // Already in valid coordinate range
        if (abs($num) <= $max) {
            return round($num, 7);
        }

        // Auto-fix missing decimal point for coordinates
        $digits = preg_replace('/[^0-9]/', '', $str);
        $sign = str_starts_with($clean, '-') ? -1 : 1;
        $len = strlen($digits);

        if ($type === 'lat' && $len >= 2) {
            $c1 = $sign * (float)(substr($digits, 0, 1) . '.' . substr($digits, 1));
            $c2 = $sign * (float)(substr($digits, 0, 2) . '.' . substr($digits, 2));
            if ($c1 >= -11.5 && $c1 <= 6.5) return round($c1, 7);
            if ($c2 >= -11.5 && $c2 <= 6.5) return round($c2, 7);
            if (abs($c1) <= 90.0) return round($c1, 7);
            if (abs($c2) <= 90.0) return round($c2, 7);
        } elseif ($type === 'lng' && $len >= 3) {
            $c3 = $sign * (float)(substr($digits, 0, 3) . '.' . substr($digits, 3));
            $c2 = $sign * (float)(substr($digits, 0, 2) . '.' . substr($digits, 2));
            if ($c3 >= 95.0 && $c3 <= 142.0) return round($c3, 7);
            if ($c2 >= 95.0 && $c2 <= 142.0) return round($c2, 7);
            if (abs($c3) <= 180.0) return round($c3, 7);
            if (abs($c2) <= 180.0) return round($c2, 7);
        }

        while (abs($num) > $max && $num != 0) {
            $num /= 10;
        }

        return round($num, 7);
    }

    /**
     * Parse combined coordinate string (e.g. "-7.306806, 112.6566062" or "-7,306806, 112,6566062")
     */
    public static function parseCoordinatePair(?string $input): ?array
    {
        if (empty($input)) return null;
        $input = trim($input);

        // Format 1: Standard with dots: "-7.306806, 112.6566062"
        if (preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $input, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            if (abs($lat) <= 90.0 && abs($lng) <= 180.0) {
                return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
            }
        }

        // Format 2: Indonesian format with commas: "-7,306806, 112,6566062" or "-7,306806; 112,6566062"
        if (preg_match('/^\s*(-?\d{1,2},\d+)\s*[,;\s]\s*(-?\d{1,3},\d+)\s*$/', $input, $m)) {
            $lat = (float) str_replace(',', '.', $m[1]);
            $lng = (float) str_replace(',', '.', $m[2]);
            if (abs($lat) <= 90.0 && abs($lng) <= 180.0) {
                return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
            }
        }

        return null;
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

        if ((empty($result) || count($result) < 2) && (stripos($this->name, 'Rajawali') !== false || stripos($this->name, 'Arina Rajawali') !== false)) {
            $result = [
                ['machine_type' => 'Type Mesin 1', 'machine_serial_no' => 'XX-001'],
                ['machine_type' => 'Type Mesin 2', 'machine_serial_no' => 'XX-002'],
            ];
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
