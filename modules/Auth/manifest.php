<?php

declare(strict_types=1);

return [
    'name' => 'Auth Module',
    'slug' => 'auth',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Auth\AuthServiceProvider::class,
    ],
    'paths' => [
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
        'database/seeders' => 'database/seeders',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
