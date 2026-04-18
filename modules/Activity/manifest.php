<?php

declare(strict_types=1);

return [
    'name' => 'Activity Module',
    'slug' => 'activity',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Activity\ActivityServiceProvider::class,
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
        'database/migrations/2026_04_11_000001_create_activities_table.php',
    ],
];
