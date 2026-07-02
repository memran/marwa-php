<?php

declare(strict_types=1);

return [
    'name' => 'Security Module',
    'slug' => 'security',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Security\SecurityServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'permissions' => [
        'security.view' => 'View Security Risk Report',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_07_02_000001_insert_security_permissions.php',
    ],
    'menu' => [
        'section' => 'Administration',
        'label' => 'Security',
        'route' => '/admin/security/risk',
        'order' => 35,
        'icon' => 'shield-alert',
        'permissions' => ['security.view'],
        'admin_only' => true,
    ],
];
