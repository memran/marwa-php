<?php

declare(strict_types=1);

return [
    'name' => 'Roles & Permissions Module',
    'slug' => 'roles',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Roles\RolesServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_15_000001_insert_roles_permissions.php',
    ],
];
