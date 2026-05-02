<?php

declare(strict_types=1);

$environment = (string) env('APP_ENV', 'production');

return [
    'enabled' => (bool) env('MODULES_ENABLED', true),
    'cache' => in_array($environment, ['local', 'development', 'dev'], true)
        ? null
        : (string) storage_path('cache/modules.php'),
];
