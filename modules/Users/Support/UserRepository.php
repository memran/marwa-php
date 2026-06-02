<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Marwa\Support\Arr;

final class UserRepository implements UserAccessPolicy
{
    public function __construct(
        private readonly UserActivityService $activity,
        private readonly UserAdminGuard $adminGuard,
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function findUser(array $vars = [], bool $includeTrashed = false): ?User
    {
        $userId = Arr::get($vars, 'id');

        if (!is_numeric($userId)) {
            return null;
        }

        $user = $includeTrashed
            ? User::withTrashed()->find((int) $userId)
            : User::find((int) $userId);

        return $user instanceof User ? $user : null;
    }

    /**
     * @param list<int> $ids
     * @return list<User>
     */
    public function usersByIds(array $ids): array
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return [];
        }

        $users = User::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        return array_values(array_filter($users, static fn ($user): bool => $user instanceof User));
    }

    /**
     * @param list<int|string> $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $id = (int) $id;
                if ($id > 0 && !in_array($id, $normalized, true)) {
                    $normalized[] = $id;
                }
            }
        }

        return $normalized;
    }

    public function findDuplicateUserByEmail(string $email, ?int $ignoreId = null): ?User
    {
        $email = User::normalizeEmail($email);

        if ($email === '') {
            return null;
        }

        $duplicate = User::findByEmailIncludingTrashed($email);

        if (!$duplicate instanceof User) {
            return null;
        }

        if ($ignoreId !== null && (int) $duplicate->getKey() === $ignoreId) {
            return null;
        }

        return $duplicate;
    }

    public function duplicateUserMessage(User $duplicate): string
    {
        if (!empty($duplicate->getAttribute('deleted_at'))) {
            return 'Duplicate user: a trashed user already uses this email. Restore that user or choose another email.';
        }

        return 'Duplicate user: this email already belongs to another user.';
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    public function createUser(array $afterState, string $password): User
    {
        return User::create([
            'name' => $afterState['name'],
            'email' => User::normalizeEmail($afterState['email']),
            'role_id' => $afterState['role_id'],
            'is_active' => $afterState['is_active'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    public function updateUser(User $user, array $afterState, ?string $password = null): void
    {
        $payload = $afterState;

        if ($password !== null && $password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $payload['email'] = User::normalizeEmail((string) $payload['email']);
        $user->fill($payload);
        $user->saveOrFail();
        $user->refresh();
    }

    public function deleteUser(User $user): void
    {
        $user->deleteOrFail();
    }

    public function restoreUser(User $user): bool
    {
        return $user->restore();
    }

    /**
     * @return array{name: string, email: string, role: string, is_active: int}
     */
    public function userSnapshot(User $user): array
    {
        return $this->activity->userSnapshot($user);
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $before
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $after
     */
    public function userStateHasChanges(array $before, array $after): bool
    {
        return $this->activity->userStateHasChanges($before, $after);
    }

    public function isLastAdminUser(User $user): bool
    {
        return $this->adminGuard->isLastAdminUser($user);
    }

    public function protectedAdminId(): int|string|null
    {
        return $this->adminGuard->protectedAdminId();
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    public function isSelfProtectedAdmin(
        User $user,
        array $afterState,
        \App\Modules\Auth\Support\AuthManager $auth
    ): bool {
        return $this->adminGuard->isSelfProtectedAdmin($user, $afterState, $auth);
    }

    public function isActiveSessionUser(User $user, \App\Modules\Auth\Support\AuthManager $auth): bool
    {
        return $this->adminGuard->isActiveSessionUser($user, $auth);
    }
}
