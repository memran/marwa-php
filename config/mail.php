<?php
    return [
        'default' => env('MAIL_DRIVER','smtp'),
        'from' => ['test@test.com'=> 'Jason Fred'],
        'reply_to' => ['address' => 'example@example.com', 'name' => 'App Name'],
        'smtp' => [
            'transport'=>'smtp' ,
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT',25),
            'username' => env('MAIL_USERNAME'),
            'password'=> env('MAIL_PASSWORD'),
            'from' => ['dev@marwaphp.com'=> 'Mohammad Emran'],
            'encryption'=> 'tls', //ssl or tls
        ],
        'sendmail' => [
          'transport' => 'sendmail',
          'path' => '/usr/sbin/sendmail -bs'
        ],
        'mailgun' =>
        [
            'transport' => 'smtp',
            'domain' => 'your-mailgun-domain',
            'secret' => 'your-mailgun-key',
            'endpoint' => 'api.eu.mailgun.net',
        ]

    ];
