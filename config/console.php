<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Marwa Console'),
    'version' => env('APP_VERSION', 'dev'),
    'commands' => [],
    'discover' => [
        [
            'path' => 'app/Commands',
        ],
    ],
    'autoDiscover' => [
        [
            'namespace' => 'Marwa\\Db\\Console\\Commands',
            'optional' => true,
        ],
    ],
];
