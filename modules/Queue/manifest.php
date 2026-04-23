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
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
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
        'route' => 'admin.queue.index',
        'icon' => 'inbox',
        'permissions' => ['queue.view'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_23_132005_create_queue_jobs_table.php',
        'database/migrations/2026_04_23_000001_insert_queue_permissions.php',
    ],
    'seeders' => [
        'database/seeders/QueuePermissionsSeeder.php',
    ],
];
