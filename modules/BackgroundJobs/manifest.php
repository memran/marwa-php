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
    ],
    'permissions' => [
        'background_jobs.view' => 'View Background Jobs',
        'background_jobs.run' => 'Run Background Jobs',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'seeders' => [
        'database/seeders/BackgroundJobsPermissionsSeeder.php',
    ],
];
