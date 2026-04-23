<?php

declare(strict_types=1);

return [
    'name' => 'Background Jobs Module',
    'slug' => 'background-jobs',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\BackgroundJobs\BackgroundJobsServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
    ],
    'permissions' => [
        'background_jobs.view' => 'View Background Jobs',
        'background_jobs.run' => 'Run Background Jobs',
    ],
    'menu' => [
        'section' => 'System',
        'label' => 'Background Jobs',
        'route' => 'admin.background-jobs.index',
        'icon' => 'clock-3',
        'permissions' => ['background_jobs.view'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'tasks' => [
        'heartbeat' => [
            'type' => 'command',
            'command' => 'background-jobs:heartbeat',
            'schedule' => [
                'everyMinute' => true,
            ],
            'withoutOverlapping' => true,
            'description' => 'Writes a heartbeat line so you can verify the scheduler is running.',
        ],
    ],
    'migrations' => [
        'database/migrations/2026_04_23_132004_create_schedule_jobs_table.php',
        'database/migrations/2026_04_23_000001_insert_background_jobs_permissions.php',
    ],
];
