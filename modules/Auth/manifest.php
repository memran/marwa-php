<?php

declare(strict_types=1);

return [
    'name' => 'Auth Module',
    'slug' => 'auth',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Auth\AuthServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
        'database/seeders' => 'database/seeders',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_10_000002_create_password_reset_tokens_table.php',
    ],
];
