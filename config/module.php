<?php

declare(strict_types=1);

return [
    'enabled' => env('AUTH_MODULE_ENABLED', false),
    'paths' => [
        module_path('Auth'),
    ],
    'cache' => bootstrap_path('cache/modules.php'),
    'forceRefresh' => env('APP_DEBUG', false),
    'commandPaths' => [
        'Console/Commands',
    ],
    'commandConventions' => [
        'Console/Commands',
    ],
];
