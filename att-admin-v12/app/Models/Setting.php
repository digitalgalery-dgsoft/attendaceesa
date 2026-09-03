<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('public_app_system_setting_array_v2');
            Cache::forget('global_landing_stats_active_v3');
        });
    }
}
