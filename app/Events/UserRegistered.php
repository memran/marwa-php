<?php

namespace App\Events;

use Marwa\Framework\Adapters\Event\AbstractEvent;

class UserRegistered extends AbstractEvent
{

    public function __construct(public readonly string $username) {}
}
