<?php

return [
    'base_url' => env('NEXUS_ENDPOINT', env('NEXUS_BASE_URL', 'https://api.nexusggr.eu')),
    'api_server' => env('NEXUS_ENDPOINT', env('GGR_API_SERVER', 'https://api.nexusggr.eu')),
    'agent_code' => env('NEXUS_AGENT_CODE', env('GGR_AGENT_CODE', 'neonwin')),
    'agent_secret' => env('NEXUS_AGENT_SECRET', env('GGR_AGENT_SECRET', '5562754eb33e45937673a6bacdd8be9d')),
    'agent_token' => env('NEXUS_AGENT_TOKEN', env('NEXUS_TOKEN', env('GGR_AGENT_TOKEN', env('NEXUS_AGENT_SECRET', '87f3f75c9501543d36bb0ebfcb473a72')))),
    'mock_mode' => env('NEXUS_MOCK_MODE', env('GGR_MOCK_MODE', false)),
    'lobby_url' => env('APP_URL', 'https://neonwin.co.uk'),
    'currency' => env('NEXUS_CURRENCY', env('DEFAULT_CURRENCY', 'COINS')),
    'denomination_rate' => (int) env('NEXUS_DENOMINATION_RATE', 1),
];
