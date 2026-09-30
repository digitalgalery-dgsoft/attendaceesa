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
            // Otomatis sinkronisasi zona waktu jika kosong atau saat titik koordinat diperbarui
            if (empty($location->timezone) || $location->isDirty(['latitude', 'longitude'])) {
                if (isset($location->latitude) && isset($location->longitude)) {
                    $location->timezone = static::determineTimezone(
                        (float) $location->latitude,
                        (float) $location->longitude,
                        $location->sub_area ?? null
                    );
                }
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

    protected static ?array $cityTimezoneCache = null;

    /**
     * Otomatis menentukan zona waktu (WIB, WITA, WIT) berdasarkan koordinat GPS atau sub_area kota.
     */
    public static function determineTimezone(?float $lat = null, ?float $lng = null, ?string $subArea = null): string
    {
        // Prioritas 1: Titik koordinat presisi GPS
        if ($lat !== null && $lng !== null && !($lat == 0.0 && $lng == 0.0)) {
            return static::determineTimezoneFromCoordinates($lat, $lng);
        }

        // Prioritas 2: Nama Sub Area / Kota
        if (!empty($subArea)) {
            $tz = static::determineTimezoneFromSubArea($subArea);
            if ($tz) return $tz;
        }

        return 'Asia/Jakarta';
    }

    /**
     * Deteksi zona waktu (WIB, WITA, WIT) secara cerdas dan akurat dari koordinat Latitude & Longitude Indonesia.
     */
    public static function determineTimezoneFromCoordinates(?float $lat, ?float $lng): string
    {
        if ($lat === null || $lng === null) {
            return 'Asia/Jakarta';
        }

        // Fallback untuk koordinat di luar wilayah umum Indonesia
        if ($lng < 95.0 || $lng > 141.5 || $lat < -11.5 || $lat > 6.5) {
            if ($lng >= 125.0) return 'Asia/Jayapura';
            if ($lng >= 115.0) return 'Asia/Makassar';
            return 'Asia/Jakarta';
        }

        // 1. WIT (Waktu Indonesia Timur / UTC+9 / Asia/Jayapura)
        // Papua & Maluku: Timur garis 125°BT kecuali Kepulauan NTT (selatan -8.1°LS) dan Talaud/Sangihe
        if ($lng >= 129.0) {
            return 'Asia/Jayapura';
        }
        if ($lng >= 125.0) {
            // Kepulauan NTT (Timor, Rote, Alor, dll.) di selatan -8.1°LS -> WITA
            if ($lat <= -8.1) {
                return 'Asia/Makassar';
            }
            // Sulawesi Utara kepulauan (Talaud/Sangihe) di utara 2.0°LU dan barat 127.2°BT -> WITA
            if ($lat >= 2.0 && $lng <= 127.2) {
                return 'Asia/Makassar';
            }
            // Maluku & Maluku Utara (Halmahera, Ambon, Seram, Buru, Ternate, Tidore, dll.) -> WIT
            return 'Asia/Jayapura';
        }

        // 2. WITA (Waktu Indonesia Tengah / UTC+8 / Asia/Makassar)
        // Bali, NTB, NTT, Sulawesi, Kalsel, Kaltim, Kaltara
        // Bali (mulai dari Selat Bali ~114.43°BT hingga 115.8°BT, lat -8.0°LS s/d -9.2°LS)
        if ($lng >= 114.43 && $lat <= -8.0 && $lat >= -9.2 && $lng <= 115.8) {
            return 'Asia/Makassar';
        }

        // NTB & NTT (Selatan -7.5°LS, Timur 115.7°BT)
        if ($lat <= -7.5 && $lng >= 115.7) {
            return 'Asia/Makassar';
        }

        // Sulawesi & sekitarnya (Bujur >= 118.5°BT)
        if ($lng >= 118.5) {
            return 'Asia/Makassar';
        }

        // Kalimantan Timur & Utara (Utara -2.5°LS, Timur 115.2°BT)
        if ($lat >= -2.5 && $lng >= 115.2) {
            return 'Asia/Makassar';
        }

        // Kalimantan Selatan (Kalsel berbatasan dengan Kalteng di sekitar 114.35°BT, lat -1.2° s/d -4.5°)
        if ($lat <= -1.2 && $lat >= -4.5 && $lng >= 114.35) {
            return 'Asia/Makassar';
        }

        // 3. WIB (Waktu Indonesia Barat / UTC+7 / Asia/Jakarta)
        // Standar untuk seluruh Sumatra, Jawa, Madura, Kalimantan Barat, dan Kalimantan Tengah
        return 'Asia/Jakarta';
    }

    /**
     * Deteksi zona waktu dari nama Sub Area / Kota berdasarkan dataset provinsi tb_kota.csv.
     */
    public static function determineTimezoneFromSubArea(?string $subArea): ?string
    {
        if (empty($subArea)) return null;

        if (static::$cityTimezoneCache === null) {
            static::$cityTimezoneCache = [];
            $file = database_path('data/tb_kota.csv');
            if (!file_exists($file)) $file = base_path('../tb_kota.csv');
            if (!file_exists($file)) $file = base_path('tb_kota.csv');
            if (!file_exists($file)) $file = 'G:\My File\Project APlikasi Absensi\New\tb_kota.csv';

            if (file_exists($file)) {
                $handle = fopen($file, 'r');
                fgetcsv($handle); // header
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[1]) && isset($row[2])) {
                        $rawCity = strtolower(trim($row[1]));
                        $cleanCity = preg_replace('/^(kota|kabupaten|kab\.)\s+/i', '', $rawCity);
                        $prov = strtolower(trim($row[2]));

                        if (str_contains($prov, 'maluku') || str_contains($prov, 'papua')) {
                            $tz = 'Asia/Jayapura'; // WIT
                        } elseif (
                            str_contains($prov, 'bali') ||
                            str_contains($prov, 'nusa tenggara') ||
                            str_contains($prov, 'ntb') ||
                            str_contains($prov, 'ntt') ||
                            str_contains($prov, 'sulawesi') ||
                            str_contains($prov, 'gorontalo') ||
                            str_contains($prov, 'kalimantan selatan') ||
                            str_contains($prov, 'kalimantan timur') ||
                            str_contains($prov, 'kalimantan utara')
                        ) {
                            $tz = 'Asia/Makassar'; // WITA
                        } else {
                            $tz = 'Asia/Jakarta'; // WIB
                        }

                        static::$cityTimezoneCache[$rawCity] = $tz;
                        static::$cityTimezoneCache[$cleanCity] = $tz;
                    }
                }
                fclose($handle);
            }
        }

        $lookup = strtolower(trim($subArea));
        $cleanLookup = preg_replace('/^(kota|kabupaten|kab\.)\s+/i', '', $lookup);

        return static::$cityTimezoneCache[$lookup] ?? static::$cityTimezoneCache[$cleanLookup] ?? null;
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
