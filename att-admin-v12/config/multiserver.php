<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Server Cluster Topology (ESA Groups 23.511 Karyawan)
    |--------------------------------------------------------------------------
    |
    | Configuration for 3 Dedicated Production Cloud Servers + Gateway Routing
    |
    */

    'current_server_id' => env('CURRENT_SERVER_ID', 'server_gateway'), // server_1, server_2, server_3, server_gateway

    'gateway_url' => env('SERVER_GATEWAY_URL', 'https://api.esa-solutions.id'),

    'media_cdn_url' => env('MEDIA_STORAGE_URL', 'https://storage.esa-solutions.id'),

    'servers' => [
        'server_1' => [
            'name' => 'PT Arina Multi Karya',
            'code' => 'AMK',
            'public_url' => env('SERVER_1_PUBLIC_URL', 'https://amk.esa-solutions.id'),
            'api_base_url' => env('SERVER_1_API_URL', 'https://amk.esa-solutions.id/api'),
            'internal_ip' => env('SERVER_1_INTERNAL_IP', '38.103.170.235'),
            'companies' => [
                'PT Arina Multi Karya',
                'Arina Multi Karya',
                'AMK',
            ],
            'company_ids' => [1],
        ],

        'server_2' => [
            'name' => 'Gabungan 3 PT (ATB + ATK + ABO)',
            'code' => 'ATB_ATK_ABO',
            'public_url' => env('SERVER_2_PUBLIC_URL', 'https://atk.esa-solutions.id'),
            'api_base_url' => env('SERVER_2_API_URL', 'https://atk.esa-solutions.id/api'),
            'internal_ip' => env('SERVER_2_INTERNAL_IP', '38.103.170.224'),
            'companies' => [
                'PT Anugrah Talenta Berkarya',
                'PT Anugrah Terpercaya Kerja',
                'PT Abadi Berkat Odelia',
                'Anugrah Talenta Berkarya',
                'Anugrah Terpercaya Kerja',
                'Abadi Berkat Odelia',
                'ATB',
                'ATK',
                'ABO',
            ],
            'company_ids' => [2, 3, 4],
        ],

        'server_3' => [
            'name' => 'PT Alva Karya Perkasa',
            'code' => 'AKP',
            'public_url' => env('SERVER_3_PUBLIC_URL', 'https://akp.esa-solutions.id'),
            'api_base_url' => env('SERVER_3_API_URL', 'https://akp.esa-solutions.id/api'),
            'internal_ip' => env('SERVER_3_INTERNAL_IP', '38.103.170.223'),
            'companies' => [
                'PT Alva Karya Perkasa',
                'Alva Karya Perkasa',
                'AKP',
            ],
            'company_ids' => [5],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal Shared Secret for Cross-Server Signed Webhooks / API
    |--------------------------------------------------------------------------
    */
    'internal_api_secret' => env('INTERNAL_SERVER_SECRET', 'esa_cross_server_secret_2026_dgsoft'),
];
