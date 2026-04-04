<?php

declare(strict_types=1);

return [
    'viewsPath' => resources_path() . DIRECTORY_SEPARATOR . 'views',
    'cachePath' => storage_path('cache') . DIRECTORY_SEPARATOR . 'views',
    'debug' => env('APP_DEBUG', false),
    'defaultTheme' => 'default',
];
