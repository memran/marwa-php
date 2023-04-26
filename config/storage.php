<?php
return [
    'local' => [
        'path' => storage_path(),
        'visibility'=> [
            'file' => [
                'public' => 0640,
                'private' => 0604,
                ],
            'dir' => [
                'public' => 0740,
                'private' => 7604,
                ]
            ]
        ],
    'ftp' => [
        'host' => 'hostname', // required
        'root' => '/root/path/', // required
        'username' => 'username', // required
        'password' => 'password', // required
        'visibility'=> [
            'file' => [
                'public' => 0640,
                'private' => 0604,
            ],
            'dir' => [
                'public' => 0740,
                'private' => 7604,
            ]
        ]
    ],
    'sftp' => [
        'host' => 'hostname', // required
        'root' => '/root/path/', // required
        'username' => 'username', // required
        'password' => 'password', // required
        'visibility'=> [
            'file' => [
                'public' => 0640,
                'private' => 0604,
            ],
            'dir' => [
                'public' => 0740,
                'private' => 7604,
            ]
        ]
    ],
    's3' => [
        'key'    => 'your-key',
        'secret' => 'your-secret',
        'region' => 'your-region',
        'version' => 'latest|version',
        'visibility' => 'public', // public or private
        'bucket' =>'your-bucket-name', //required
        'prefix' => 'optional/path/prefix'
    ]


];