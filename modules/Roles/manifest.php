<?php

declare(strict_types=1);

return [
    'name' => 'Roles & Permissions Module',
    'slug' => 'roles',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Roles\RolesServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
