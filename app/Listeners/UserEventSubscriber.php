<?php

namespace App\Listeners;

use App\Events\UserRegistered;

class UserEventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => [
                ['onUserRegistered', 10],
                ['sendVerificationEmail', 5],
            ],
            'user.logged_in' => 'onUserLoggedIn',
        ];
    }

    public function onUserRegistered(UserRegistered $event)
    {
        // Handle user registration logic
    }

    public function sendVerificationEmail(UserRegistered $event)
    {
        // Send verification email logic
    }

    public function onUserLoggedIn($event)
    {
        // Handle user login logic
    }
}
