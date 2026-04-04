<?php

declare(strict_types=1);

return [
    'module' => [
        'enabled' => env('AUTH_MODULE_ENABLED', false),
    ],
    'defaults' => [
        'admin_role' => env('AUTH_ADMIN_ROLE', 'admin'),
        'default_role' => env('AUTH_DEFAULT_ROLE', 'user'),
    ],
    'session' => [
        'user_key' => 'auth_user_id',
        'intended_key' => 'auth_intended_url',
    ],
    'remember' => [
        'cookie' => env('AUTH_REMEMBER_COOKIE', 'marwa_auth_remember'),
        'ttl' => (int) env('AUTH_REMEMBER_TTL', 60 * 60 * 24 * 30),
    ],
    'password_reset' => [
        'ttl' => (int) env('AUTH_PASSWORD_RESET_TTL', 60 * 60),
    ],
    'seed' => [
        'admin_name' => env('AUTH_ADMIN_NAME', 'Administrator'),
        'admin_email' => env('AUTH_ADMIN_EMAIL', 'admin@marwa.test'),
        'admin_password' => env('AUTH_ADMIN_PASSWORD', 'ChangeMe123!'),
    ],
];
