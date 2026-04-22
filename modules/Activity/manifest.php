<?php

declare(strict_types=1);

return [
    'name' => 'User Activity Module',
    'slug' => 'user-activity',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Activity\ActivityServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'listeners' => [
        Marwa\Framework\Adapters\Event\RequestHandled::class => [
            App\Modules\Activity\Listeners\RecordModuleActivityListener::class,
        ],
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
        'database/migrations/2026_04_11_000001_create_activities_table.php',
        'database/migrations/2026_04_23_000001_add_request_metadata_to_activities_table.php',
        'database/migrations/2026_04_11_000002_insert_activity_permissions.php',
    ],
];
