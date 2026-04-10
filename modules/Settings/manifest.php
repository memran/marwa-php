<?php

declare(strict_types=1);

return [
    'name' => 'Settings Module',
    'slug' => 'settings',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Settings\SettingsServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
