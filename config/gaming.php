<?php

return [
    'provider' => env('GAMING_PROVIDER', 'nexus'),
    'flexrix' => [
        'base_url' => env('FLEXRIX_API_URL', 'https://api.upaflex.online'),
        'casino_key' => env('FLEXRIX_CASINO_MERCHANT_KEY', ''),
        'casino_secret' => env('FLEXRIX_CASINO_API_SECRET', ''),
        'sportsbook_key' => env('FLEXRIX_SPORTSBOOK_MERCHANT_KEY', ''),
        'sportsbook_secret' => env('FLEXRIX_SPORTSBOOK_API_SECRET', ''),
        'sportsbook_url' => env('FLEXRIX_SPORTSBOOK_URL', ''),
        'force_ipv4' => (bool) env('FLEXRIX_FORCE_IPV4', true),
    ],
];
