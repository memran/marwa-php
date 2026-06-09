<?php

declare(strict_types=1);

use Marwa\ErrorHandler\Support\FallbackRenderer;
use App\Support\Errors\ThemeErrorRenderer;

return [
    'enabled' => env('ERROR_ENABLED', true),
    'appName' => env('APP_NAME', 'MarwaPHP'),
    'environment' => env('APP_ENV', 'production'),
    'useLogger' => env('ERROR_USE_LOGGER', true),
    'useDebugReporter' => env('ERROR_USE_DEBUG_REPORTER', true),
    'renderer' => ThemeErrorRenderer::class,
];
