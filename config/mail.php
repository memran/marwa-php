<?php

declare(strict_types=1);

$appName = (string) env('APP_NAME', 'MarwaPHP');

return [
    'enabled' => env('MAIL_ENABLED', true),
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'charset' => env('MAIL_CHARSET', 'UTF-8'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@mygoti.net'),
        'name' => env('MAIL_FROM_NAME', $appName !== '' ? $appName : 'MarwaPHP'),
    ],
    'smtp' => [
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => env('MAIL_PORT', 1025),
        'encryption' => env('MAIL_ENCRYPTION', null),
        'username' => env('MAIL_USERNAME', null),
        'password' => env('MAIL_PASSWORD', null),
        'authMode' => env('MAIL_AUTH_MODE', null),
        'timeout' => env('MAIL_TIMEOUT', 30),
    ],
    'sendmail' => [
        'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs'),
    ],
];
