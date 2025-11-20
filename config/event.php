<?php
return [
    'listeners' => [
        App\Events\UserRegistered::class => [
            [App\Listeners\SendWelcomeEmail::class, 'handle']
        ],
        'user.registered' => [
            App\Listeners\LogUserRegistered::class,
        ],
    ],
    'subscribers' => [
        App\Listeners\UserEventSubscriber::class,
    ],

];
