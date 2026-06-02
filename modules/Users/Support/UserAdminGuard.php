<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class UserAdminGuard
{
    private ?int $adminRoleId = null;

    public function adminRoleId(): ?int
    {
        if ($this->adminRoleId !== null) {
            return $this->adminRoleId;
        }

        $role = Role::findBySlug('admin');

        return $this->adminRoleId = $role === null ? null : (int) $role->getKey();
    }

    public function protectedAdminId(): int|string|null
    {
        $adminRoleId = $this->adminRoleId();
        if ($adminRoleId === null) {
            return null;
        }

        $users = User::where('role_id', '=', $adminRoleId)
            ->whereNull('deleted_at');

        if ($users->count() !== 1) {
            return null;
        }

        return $users->first()?->getKey();
    }

    public function isLastAdminUser(User $user): bool
    {
        $role = $user->role();
        if ($role === null || $role->getAttribute('slug') !== 'admin') {
            return false;
        }

        $adminRoleId = $this->adminRoleId();
        if ($adminRoleId === null) {
            return false;
        }

        return User::where('role_id', '=', $adminRoleId)
            ->whereNull('deleted_at')
            ->count() <= 1;
    }

    public function isActiveSessionUser(User $user, \App\Modules\Auth\Support\AuthManager $auth): bool
    {
        $currentUser = $auth->user();

        if (!$currentUser instanceof User) {
            return false;
        }

        $currentEmail = User::normalizeEmail((string) $currentUser->getAttribute('email'));
        $targetEmail = User::normalizeEmail((string) $user->getAttribute('email'));

        return ($currentEmail !== '' && $currentEmail === $targetEmail)
            || $currentUser->getKey() === $user->getKey();
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    public function isSelfProtectedAdmin(User $user, array $afterState, \App\Modules\Auth\Support\AuthManager $auth): bool
    {
        $currentUser = $auth->user();
        $currentEmail = $currentUser instanceof User
            ? User::normalizeEmail((string) $currentUser->getAttribute('email'))
            : '';
        $targetEmail = User::normalizeEmail((string) $user->getAttribute('email'));

        return $currentEmail !== ''
            && $currentEmail === $targetEmail
            && $this->isLastAdminUser($user)
            && $afterState['is_active'] === 0;
    }
}
