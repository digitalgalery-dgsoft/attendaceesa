<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
        'new_count' => 'integer',
        'update_count' => 'integer',
        'resign_count' => 'integer',
        'total_employee_count' => 'integer',
    ];

    public static function pruneOlderLogs(int $keep = 200): void
    {
        $keepIds = static::orderBy('created_at', 'desc')->limit($keep)->pluck('id')->toArray();
        if (!empty($keepIds)) {
            static::whereNotIn('id', $keepIds)->delete();
        }
    }

    protected static function booted(): void
    {
        static::created(function ($log) {
            static::pruneOlderLogs(200);
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
