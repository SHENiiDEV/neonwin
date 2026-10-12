<?php

return [
    'force_ipv4' => (bool) env('NEXUS_FORCE_IPV4', false),
    'base_url' => env('NEXUS_ENDPOINT', env('NEXUS_BASE_URL', 'https://api.nexusggr.eu')),
    'agent_code' => env('NEXUS_AGENT_CODE', env('GGR_AGENT_CODE', '')),
    'agent_secret' => env('NEXUS_AGENT_SECRET', env('GGR_AGENT_SECRET', '')),
    'agent_token' => env('NEXUS_AGENT_TOKEN', env('NEXUS_TOKEN', env('GGR_AGENT_TOKEN', ''))),
    'lobby_url' => env('NEXUS_LOBBY_URL', env('APP_URL')),
    'currency' => 'EUR',
    'eur_agent_confirmed' => (bool) env('NEXUS_EUR_AGENT_CONFIRMED', false),
    'rtp_enabled' => (bool) env('NEXUS_RTP_ENABLED', false),
    'agent_type' => env('NEXUS_AGENT_TYPE', 'normal'),
    'rtp_providers' => array_filter(explode(',', env('NEXUS_RTP_PROVIDERS', ''))),
    'rewards_enabled' => (bool) env('NEXUS_REWARDS_ENABLED', false),
];
