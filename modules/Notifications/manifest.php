<?php

declare(strict_types=1);

return [
    'name' => 'Notifications Module',
    'slug' => 'notifications',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Notifications\NotificationsServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
