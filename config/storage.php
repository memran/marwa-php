<?php

declare(strict_types=1);

return [
    'default' => env('STORAGE_DEFAULT', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => base_path('storage/app'),
            'visibility' => 'private',
        ],
        'public' => [
            'driver' => 'local',
            'root' => base_path('storage/app/public'),
            'visibility' => 'public',
        ],
    ],
];
