<?php

declare(strict_types=1);

return [
    'listeners' => [
        Marwa\Framework\Adapters\Event\ModulesBootstrapped::class => [
            App\Listeners\RunModuleMigrations::class,
        ],
    ],
    'subscribers' => [],
];
