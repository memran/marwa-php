<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;

interface UserAccessPolicy
{
    public function protectedAdminId(): int|string|null;

    public function isActiveSessionUser(User $user, AuthManager $auth): bool;
}
