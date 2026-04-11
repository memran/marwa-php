<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('MODULES_ENABLED', true),
    'cache' => (string) storage_path('cache/modules.php'),
];
