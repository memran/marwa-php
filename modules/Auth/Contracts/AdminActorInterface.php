<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Models\Role;

interface AdminActorInterface extends PermissionAwareUser
{
    public function getAttribute(string $key): mixed;

    public function getId(): ?int;

    public function role(): ?Role;
}
