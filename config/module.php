<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('MODULES_ENABLED', true),
    'cache' => (string) env('APP_MODULE_CACHE', storage_path('cache/modules.php')),
];
