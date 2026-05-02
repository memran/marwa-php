<?php

declare(strict_types=1);

return [
    'enabled' => env('SESSION_ENABLED', true),
    'autoStart' => env('SESSION_AUTO_START', false),
    'name' => env('SESSION_NAME', 'marwa_session'),
    'lifetime' => env('SESSION_LIFETIME', 7200),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN', ''),
    'secure' => env('SESSION_SECURE', in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)),
    'httpOnly' => env('SESSION_HTTP_ONLY', true),
    'sameSite' => env('SESSION_SAME_SITE', 'Lax'),
    'encrypt' => env('SESSION_ENCRYPT', true),
    'savePath' => env('SESSION_SAVE_PATH', base_path('storage/sessions')),
];
