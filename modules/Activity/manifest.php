<?php

declare(strict_types=1);

return [
    'name' => 'User Activity Module',
    'slug' => 'user-activity',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Activity\ActivityServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'listeners' => [
        App\Modules\Activity\Events\ActivityRecordingRequested::class => [
            App\Modules\Activity\Listeners\RecordActivityRecordingListener::class,
        ],
        Marwa\Framework\Adapters\Event\RequestHandled::class => [
            App\Modules\Activity\Listeners\RecordModuleActivityListener::class,
        ],
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_11_000001_create_activities_table.php',
        'database/migrations/2026_04_23_000001_add_request_metadata_to_activities_table.php',
        'database/migrations/2026_04_11_000002_insert_activity_permissions.php',
    ],
    'menu' => [
        'section' => 'Identity & Access',
        'label' => 'Audit Logs',
        'route' => '/admin/activity',
        'order' => 40,
        'icon' => 'activity',
        'permissions' => ['activity.view'],
        'admin_only' => true,
    ],
];
