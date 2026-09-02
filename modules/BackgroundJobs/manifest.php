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
        'database/seeders' => 'database/seeders',
    ],
    'permissions' => [
        'background_jobs.view' => 'View Background Jobs',
        'background_jobs.run' => 'Run Background Jobs',
    ],
    'menu' => [
        'name' => 'admin.background-jobs',
        'label' => 'Background Jobs',
        'url' => '/admin/background-jobs',
        'parent' => 'admin.administration',
        'order' => 30,
        'icon' => 'clock-3',
        'permission' => 'background_jobs.view',
        'roles' => ['admin'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'seeders' => [
        'database/seeders/BackgroundJobsPermissionsSeeder.php',
    ],
];
