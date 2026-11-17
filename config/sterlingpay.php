<?php

return [
    'enabled' => (bool) env('STERLINGPAY_ENABLED', false),
    'api_url' => env('STERLINGPAY_API_URL', 'https://api.sterling-pay.com'),
    'account_id' => env('STERLINGPAY_ACCOUNT_ID', ''),
    'api_key' => env('STERLINGPAY_API_KEY', ''),
    'webhook_secret' => env('STERLINGPAY_WEBHOOK_SECRET', ''),
    'service' => env('STERLINGPAY_SERVICE', 'payment_card_eur_hpp'),
    'hpp_host' => env('STERLINGPAY_HPP_HOST', 'pay.sterling-pay.com'),
    'return_hosts' => array_map('trim', explode(',', env('STERLINGPAY_RETURN_HOSTS', 'neonwin.co.uk,neonwin132.com,neonwin281.com'))),
    'test_mode' => (bool) env('STERLINGPAY_TEST_MODE', true),
];
