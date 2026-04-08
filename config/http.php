<?php

declare(strict_types=1);

return [
    'enabled' => env('HTTP_ENABLED', true),
    'default' => env('HTTP_DEFAULT', 'default'),
    'clients' => [
        'default' => [
            'base_uri' => env('HTTP_DEFAULT_BASE_URI', null),
            'timeout' => env('HTTP_DEFAULT_TIMEOUT', 30.0),
            'connect_timeout' => env('HTTP_DEFAULT_CONNECT_TIMEOUT', 10.0),
            'http_errors' => env('HTTP_DEFAULT_HTTP_ERRORS', false),
            'verify' => env('HTTP_DEFAULT_VERIFY', true),
            'headers' => [],
        ],
    ],
];
