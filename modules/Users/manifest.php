<?php

declare(strict_types=1);

return [
    'name' => 'Users Module',
    'slug' => 'users',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Users\UsersServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_10_000001_create_users_table.php',
    ],
];
