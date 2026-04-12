<?php

declare(strict_types=1);

return [
    'name' => 'Users Module',
    'slug' => 'users',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Users\UsersServiceProvider::class,
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
