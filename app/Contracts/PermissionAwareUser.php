<?php

declare(strict_types=1);

namespace App\Contracts;

interface PermissionAwareUser
{
    public function hasPermission(string $permission): bool;
}
