<?php

declare(strict_types=1);

return [
    'name' => 'Queue Module',
    'slug' => 'queue',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Queue\QueueServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/seeders' => 'database/seeders',
    ],
    'permissions' => [
        'queue.view' => 'View Queue Jobs',
        'queue.retry' => 'Retry Queue Jobs',
    ],
    'menu' => [
        'section' => 'Administration',
        'label' => 'Queue',
        'route' => '/admin/queue',
        'order' => 60,
        'icon' => 'inbox',
        'permissions' => ['queue.view'],
        'admin_only' => true,
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'seeders' => [
        'database/seeders/QueuePermissionsSeeder.php',
    ],
];
