<?php

return [
    'enable' => env("APP_ENV") == 'development' ? true : false,
    'storage' => [
        'driver'    => 'file',
        'path'      => storage_path() . DIRECTORY_SEPARATOR . 'logs',
        'prefix'    => 'myapp',
        'max_bytes' => '10MB',
        'level'     => 'debug'
    ],
    'filter' => ['password', 'token', 'authorization']
];
