<?php

declare(strict_types=1);

return [
    'enabled' => env('MODULES_ENABLED', false),
    'paths' => [],
    'cache' => bootstrap_path('cache/modules.php'),
    'forceRefresh' => in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true),
    'commandPaths' => [
        'Console/Commands',
    ],
    'commandConventions' => [
        'Console/Commands',
    ],
];
