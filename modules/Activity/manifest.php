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
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
