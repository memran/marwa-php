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
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_15_000001_insert_database_manager_permissions.php',
    ],
];
