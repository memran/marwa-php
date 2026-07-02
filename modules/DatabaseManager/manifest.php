<?php

declare(strict_types=1);

return [
    'name' => 'Database Manager',
    'slug' => 'database-manager',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\DatabaseManager\DatabaseManagerServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'permissions' => [
        'database.view' => 'View Database',
        'database.query' => 'Query Database',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_15_000001_insert_database_manager_permissions.php',
    ],
    'menu' => [
        'section' => 'Administration',
        'label' => 'Database',
        'route' => '/admin/database',
        'order' => 50,
        'icon' => 'database',
        'permissions' => ['database.view'],
        'admin_only' => true,
    ],
];
