<?php

return [
    'enable' => env('LOG_ENABLE', env('APP_ENV', 'production') !== 'production'),
    'storage' => [
        'driver'    => 'file',
        'path'      => storage_path() . DIRECTORY_SEPARATOR . 'logs',
        'prefix'    => 'myapp',
        'max_bytes' => '10MB',
        'level'     => env('LOG_LEVEL', 'debug')
    ],
    'filter' => ['password', 'token', 'authorization']
];
