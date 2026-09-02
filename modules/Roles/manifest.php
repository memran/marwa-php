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
    'menu' => [
        [
            'name' => 'admin.roles',
            'label' => 'Roles',
            'url' => '/admin/roles',
            'parent' => 'admin.identity-access',
            'order' => 25,
            'icon' => 'shield-check',
            'permission' => 'roles.view',
            'roles' => ['admin'],
        ],
        [
            'name' => 'admin.permissions',
            'label' => 'Permissions',
            'url' => '/admin/permissions',
            'parent' => 'admin.identity-access',
            'order' => 30,
            'icon' => 'list-checks',
            'permission' => 'permissions.view',
            'roles' => ['admin'],
        ],
    ],
];
