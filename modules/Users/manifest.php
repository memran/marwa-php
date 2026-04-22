<?php

declare(strict_types=1);

return [
    'name' => 'Users Module',
    'slug' => 'users',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Users\UsersServiceProvider::class,
    ],
    'requires' => [
        'auth',
        'user-activity',
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
        'database/migrations/2026_04_10_000001_create_users_table.php',
        'database/migrations/2026_04_10_000002_insert_users_permissions.php',
        'database/migrations/2026_04_14_000001_add_role_id_to_users.php',
    ],
    'seeders' => [
        'database/seeders/AdminUserSeeder.php',
    ],
];
