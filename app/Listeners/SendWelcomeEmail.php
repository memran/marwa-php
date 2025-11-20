<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Psr\Log\LoggerInterface;

class SendWelcomeEmail
{

    public function handle(UserRegistered $event): void
    {
        // Simulate sending a welcome email
        //$this->logger->info("Welcome email sent to user: " . $event->username);
        //echo "Welcome email sent to user: " . $event->username . PHP_EOL;
        file_put_contents(base_path('storage/logs') . DIRECTORY_SEPARATOR . 'emails.log', "Welcome email sent to user: " . $event->username . PHP_EOL, FILE_APPEND);
    }
}
