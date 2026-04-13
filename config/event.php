<?php

declare(strict_types=1);

return [
    'listeners' => [
        App\Events\ActivityRecordRequested::class => [
            App\Listeners\RecordActivityFromEvent::class,
        ],
        Marwa\Framework\Adapters\Event\ModulesBootstrapped::class => [
            App\Listeners\RunModuleMigrations::class,
        ],
    ],
    'subscribers' => [],
];
