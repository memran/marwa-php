<?php

declare(strict_types=1);

return [
    'name' => 'Notifications Module',
    'slug' => 'notifications',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Notifications\NotificationsServiceProvider::class,
    ],
    'requires' => [
        'auth',
        'users',
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
        'database/migrations/2026_04_14_000002_create_notifications_table.php',
        'database/migrations/2026_04_14_000003_insert_notifications_permissions.php',
    ],
];
