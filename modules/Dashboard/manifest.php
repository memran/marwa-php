<?php

declare(strict_types=1);

return [
    'name' => 'Dashboard Module',
    'slug' => 'dashboard',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Dashboard\DashboardServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'assets' => 'resources/views/assets',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_15_000001_create_dashboard_widgets_table.php',
    ],
    'widgets' => [
        'app_status' => [
            'name' => 'Application Status',
            'description' => 'Shows application name and environment status',
            'size' => 'medium',
            'default' => true,
        ],
        'runtime_info' => [
            'name' => 'Runtime Info',
            'description' => 'PHP version and server API information',
            'size' => 'small',
            'default' => true,
        ],
        'memory_usage' => [
            'name' => 'Memory Usage',
            'description' => 'Current PHP memory limit',
            'size' => 'small',
            'default' => true,
        ],
        'disk_space' => [
            'name' => 'Disk Space',
            'description' => 'Available disk space for storage',
            'size' => 'small',
            'default' => true,
        ],
        'load_average' => [
            'name' => 'Load Average',
            'description' => 'Server load average',
            'size' => 'small',
            'default' => true,
        ],
        'theme_info' => [
            'name' => 'Theme Info',
            'description' => 'Current admin theme',
            'size' => 'small',
            'default' => true,
        ],
    ],
];