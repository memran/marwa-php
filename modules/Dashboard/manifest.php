<?php

declare(strict_types=1);

return [
    'name' => 'Dashboard Module',
    'slug' => 'dashboard',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Dashboard\DashboardServiceProvider::class,
    ],
    'requires' => [
        'auth',
        'dashboard-status',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_15_000001_create_dashboard_widgets_table.php',
        'database/migrations/2026_04_15_000002_insert_dashboard_permissions.php',
    ],
    'menu' => [
        'section' => 'Overview',
        'label' => 'Dashboard',
        'route' => '/admin/dashboard',
        'icon' => 'layout-dashboard',
        'permissions' => ['dashboard.view'],
    ],
];
