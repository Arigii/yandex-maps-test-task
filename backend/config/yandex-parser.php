<?php

return [
    'strategy' => env('YANDEX_PARSER_STRATEGY', 'hybrid'),
    'use_mock' => env('YANDEX_PARSER_USE_MOCK', false),
    'request_delay_ms' => env('YANDEX_REQUEST_DELAY_MS', 800),
    'max_retries' => env('YANDEX_MAX_RETRIES', 3),
    'proxy_pool' => env('YANDEX_PROXY_POOL', ''),
    'user_agent_pool' => env('YANDEX_USER_AGENT_POOL', 'Mozilla/5.0'),
];
