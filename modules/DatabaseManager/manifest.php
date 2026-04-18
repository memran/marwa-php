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
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
