<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Psr\Log\LoggerInterface;

class LogUserRegistered
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(UserRegistered $event): void
    {
        $this->logger->info("User registered: " . $event->username);
    }
}
