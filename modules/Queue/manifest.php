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
        'name' => 'admin.queue',
        'label' => 'Queue',
        'url' => '/admin/queue',
        'parent' => 'admin.administration',
        'order' => 60,
        'icon' => 'inbox',
        'permission' => 'queue.view',
        'roles' => ['admin'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'seeders' => [
        'database/seeders/QueuePermissionsSeeder.php',
    ],
];
