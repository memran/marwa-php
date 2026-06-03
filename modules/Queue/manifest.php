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
        'notifications',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/seeders' => 'database/seeders',
    ],
    'permissions' => [
        'queue.view' => 'View Queue Jobs',
        'queue.retry' => 'Retry Queue Jobs',
        'queue.work' => 'Run Queue Worker',
    ],
    'menu' => [
        'section' => 'System',
        'label' => 'Queue',
        'route' => '/admin/queue',
        'icon' => 'inbox',
        'permissions' => ['queue.view'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'seeders' => [
        'database/seeders/QueuePermissionsSeeder.php',
    ],
];
