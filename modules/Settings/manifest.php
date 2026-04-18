<?php

declare(strict_types=1);

return [
    'name' => 'Settings Module',
    'slug' => 'settings',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Settings\SettingsServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_14_000001_create_settings_table.php',
    ],
];
