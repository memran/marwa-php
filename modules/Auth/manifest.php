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
        'views' => 'views',
        'commands' => 'Console/Commands',
        'seeders' => 'Database/Seeders',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'Database/Migrations/2026_04_04_000001_create_auth_roles_table.php',
        'Database/Migrations/2026_04_04_000002_create_auth_users_table.php',
        'Database/Migrations/2026_04_04_000003_create_auth_role_user_table.php',
        'Database/Migrations/2026_04_04_000004_create_auth_password_resets_table.php',
    ],
];
