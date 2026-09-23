<?php

return [
    'base_url' => env('NEXUS_BASE_URL', 'https://api.nexusggr.eu'),
    'api_server' => env('GGR_API_SERVER', 'https://api.nexusggr.eu'),
    'agent_code' => env('NEXUS_AGENT_CODE', env('GGR_AGENT_CODE', 'zenithplay')),
    'agent_token' => env('NEXUS_AGENT_TOKEN', env('GGR_AGENT_TOKEN', '87f3f75c9501543d36bb0ebfcb473a72')),
    'agent_secret' => env('NEXUS_AGENT_SECRET', env('GGR_AGENT_SECRET', '5562754eb33e45937673a6bacdd8be9d')),
    'mock_mode' => env('GGR_MOCK_MODE', false),
    'lobby_url' => env('APP_URL', 'http://localhost:8888'),
    'currency' => env('DEFAULT_CURRENCY', 'COINS'),
    'denomination_rate' => env('NEXUS_DENOMINATION_RATE', 100000),
];
