<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ESA Shared Sync Secret Key
    |--------------------------------------------------------------------------
    | Digunakan untuk otentikasi aman pertukaran data antar server ESA.
    | Pastikan nilai ini seragam di seluruh .env ketiga server.
    */
    'secret' => env('ESA_SYNC_SECRET', 'ESA-SYNC-SECRET-KEY-2026-CROSS-ENTITY-SECURE'),

    /*
    |--------------------------------------------------------------------------
    | Daftar Server Produksi ESA Groups
    |--------------------------------------------------------------------------
    */
    'servers' => [
        'amk' => [
            'id' => 'amk',
            'name' => 'Server 1 (PT AMK)',
            'base_url' => env('ESA_SERVER_AMK_URL', 'https://amk.esa-solutions.id'),
            'alt_url'  => 'https://amk.dgsoft.web.id',
        ],
        'akp' => [
            'id' => 'akp',
            'name' => 'Server 2 (PT AKP)',
            'base_url' => env('ESA_SERVER_AKP_URL', 'https://akp.esa-solutions.id'),
            'alt_url'  => 'https://akp.dgsoft.web.id',
        ],
        'atk' => [
            'id' => 'atk',
            'name' => 'Server 3 (PT ATK / Gabungan)',
            'base_url' => env('ESA_SERVER_ATK_URL', 'https://atk.esa-solutions.id'),
            'alt_url'  => 'https://atk.dgsoft.web.id',
        ],
    ],
];
