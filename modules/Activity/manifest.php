<?php

declare(strict_types=1);

return [
    'name' => 'Activity Module',
    'slug' => 'activity',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Activity\ActivityServiceProvider::class,
    ],
    'paths' => [
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
