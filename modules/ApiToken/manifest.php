<?php

declare(strict_types=1);

return [
    'name' => 'API Token Module',
    'slug' => 'api-token',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\ApiToken\ApiTokenServiceProvider::class,
    ],
    'requires' => [
        'auth',
    ],
    'permissions' => [
        'api_token.view' => 'View API tokens',
        'api_token.create' => 'Create API tokens',
        'api_token.revoke' => 'Revoke API tokens',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_04_23_000001_create_api_tokens_table.php',
    ],
    'menu' => [
        'section' => 'API',
        'label' => 'API Tokens',
        'route' => 'admin.api-tokens.index',
        'icon' => 'key',
        'permissions' => ['api_token.view'],
    ],
];