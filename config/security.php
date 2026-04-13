<?php

declare(strict_types=1);

return [
    'enabled' => env('SECURITY_ENABLED', true),
    'csrf' => [
        'enabled' => env('CSRF_ENABLED', true),
        'field' => env('CSRF_FIELD', '_token'),
        'header' => env('CSRF_HEADER', 'X-CSRF-TOKEN'),
        'token' => env('CSRF_TOKEN_NAME', '__marwa_csrf_token'),
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'except' => [],
    ],
    'trustedHosts' => [],
    'trustedOrigins' => [],
    'throttle' => [
        'enabled' => env('SECURITY_THROTTLE_ENABLED', false),
        'prefix' => env('SECURITY_THROTTLE_PREFIX', 'security'),
        'limit' => env('SECURITY_THROTTLE_LIMIT', 60),
        'window' => env('SECURITY_THROTTLE_WINDOW', 60),
    ],
    'risk' => [
        'enabled' => env('SECURITY_RISK_ENABLED', true),
        'logPath' => storage_path('security/risk.jsonl'),
        'pruneAfterDays' => env('SECURITY_RISK_PRUNE_AFTER_DAYS', 30),
        'topCount' => env('SECURITY_RISK_TOP_COUNT', 10),
    ],
];
