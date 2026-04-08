<?php

declare(strict_types=1);

return [
    'enabled' => env('NOTIFICATION_ENABLED', true),
    'default' => ['mail'],
    'channels' => [
        'mail' => [
            'enabled' => true,
        ],
        'database' => [
            'enabled' => true,
            'connection' => 'default',
            'table' => 'notifications',
        ],
        'http' => [
            'enabled' => true,
            'client' => 'default',
            'method' => 'POST',
            'url' => null,
            'headers' => [],
        ],
        'sms' => [
            'enabled' => false,
            'client' => 'default',
            'method' => 'POST',
            'url' => null,
            'headers' => [],
        ],
        'kafka' => [
            'enabled' => false,
            'publisher' => Marwa\Framework\Contracts\KafkaPublisherInterface::class,
            'consumer' => null,
            'topic' => 'notifications',
            'topics' => [],
            'groupId' => 'marwa-framework',
            'key' => null,
            'headers' => [],
            'options' => [],
        ],
        'broadcast' => [
            'enabled' => true,
            'event' => Marwa\Framework\Notifications\Events\NotificationBroadcasted::class,
        ],
    ],
];
