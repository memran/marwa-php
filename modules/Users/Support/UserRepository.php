<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class UserRepository
{
    public function findById(int $id, bool $includeTrashed = false): ?User
    {
        if ($id <= 0) {
            return null;
        }

        $builder = $includeTrashed ? User::withTrashed() : User::query();

        return $builder->with('roleRelation')->whereKey($id)->first();
    }

    public function findByIdWithRolePermissions(int $id, bool $includeTrashed = false): ?User
    {
        if ($id <= 0) {
            return null;
        }

        $builder = $includeTrashed ? User::withTrashed() : User::query();

        return $builder->with('roleRelation', 'roleRelation.permissionsRelation')->whereKey($id)->first();
    }

    public function createUser(array $data): User
    {
        $state = $this->normalizeUserState($data);

        $user = User::create([
            'name' => $state['name'],
            'email' => $state['email'],
            'password' => password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
            'role_id' => $state['role_id'],
            'is_active' => $state['is_active'],
        ]);

        return $user->refresh();
    }

    public function updateUser(User $user, array $data, ?string $password = null): User
    {
        $state = $this->normalizeUserState($data);

        if ($password !== null && $password !== '') {
            $state['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->fill($state)->saveOrFail();

        return $user->refresh();
    }

    public function deleteUser(User $user): void
    {
        $user->deleteOrFail();
    }

    public function restoreUser(User $user): void
    {
        $user->restore();
    }

    /**
     * @param list<int|string> $ids
     * @return array{deleted:int, skipped:int}
     */
    public function bulkDelete(array $ids, ?User $actor = null): array
    {
        $deleted = 0;
        $skipped = 0;
        $userIds = $this->positiveIds($ids);
        if ($userIds === []) {
            return ['deleted' => 0, 'skipped' => 0];
        }

        $usersById = $this->usersById($userIds);
        $protectedId = $this->protectedAdminId();

        foreach ($userIds as $userId) {
            $user = $usersById[$userId] ?? null;
            if ($user === null || $userId === $protectedId || $this->sameUser($user, $actor)) {
                $skipped++;
                continue;
            }

            $this->deleteUser($user);
            unset($usersById[$userId]);
            $deleted++;
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @param list<int|string> $ids
     * @return array{updated:int, skipped:int}
     */
    public function bulkStatus(array $ids, bool $isActive, ?User $actor = null): array
    {
        $updated = 0;
        $skipped = 0;
        $userIds = $this->positiveIds($ids);
        if ($userIds === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $usersById = $this->usersById($userIds);
        $protectedId = $this->protectedAdminId();

        foreach ($userIds as $userId) {
            $user = $usersById[$userId] ?? null;
            if ($user === null || $userId === $protectedId || $this->sameUser($user, $actor)) {
                $skipped++;
                continue;
            }

            $user->setAttribute('is_active', $isActive ? 1 : 0);
            $user->save();
            unset($usersById[$userId]);
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    public function isDuplicateEmail(string $email, ?int $ignoreId = null): bool
    {
        $email = User::normalizeEmail($email);
        if ($email === '') {
            return false;
        }

        $builder = User::withTrashed()
            ->where('email', '=', $email);

        if ($ignoreId !== null) {
            $builder->where('id', '!=', $ignoreId);
        }

        return $builder->exists();
    }

    public function protectedAdminId(): ?int
    {
        $adminRoleId = $this->findAdminRoleId();

        if ($adminRoleId === null) {
            return null;
        }

        $builder = User::query()
            ->where('role_id', '=', $adminRoleId)
            ->whereNull('deleted_at');

        $users = $builder->orderBy('id', 'asc')->limit(2)->get();

        if (count($users) !== 1) {
            return null;
        }

        $user = $users[0] ?? null;

        return $user instanceof User ? (int) $user->getKey() : null;
    }

    public function isLastAdminUser(User $user): bool
    {
        $protectedId = $this->protectedAdminId();

        return $protectedId !== null && (int) $user->getKey() === $protectedId;
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $state
     */
    public function wouldBreakAdminAccess(User $user, array $state): bool
    {
        if (!$this->isLastAdminUser($user)) {
            return false;
        }

        return (int) $user->getAttribute('role_id') !== $state['role_id'] || $state['is_active'] !== 1;
    }

    public function sameUser(?User $user, ?User $actor): bool
    {
        return $user instanceof User
            && $actor instanceof User
            && (int) $user->getKey() === (int) $actor->getKey();
    }

    public function canAssignRole(?User $actor, int $roleId): bool
    {
        if (!$actor instanceof User || $roleId <= 0) {
            return false;
        }

        $targetRole = $this->roleById($roleId);
        $actorRole = $this->roleById((int) $actor->getAttribute('role_id'));

        if (!$targetRole instanceof Role || !$actorRole instanceof Role) {
            return false;
        }

        $actorLevel = (int) $actorRole->getAttribute('level');
        $targetLevel = (int) $targetRole->getAttribute('level');

        return $actorLevel > 0 && $targetLevel > 0 && $targetLevel <= $actorLevel;
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return Role::query()->orderBy('name', 'asc')->get();
    }

    public function roleById(int $roleId): ?Role
    {
        if ($roleId <= 0) {
            return null;
        }

        $role = Role::find($roleId);

        return $role instanceof Role ? $role : null;
    }

    private function findAdminRoleId(): ?int
    {
        $role = Role::findBy('slug', 'admin');

        return $role !== null ? (int) $role->getKey() : null;
    }

    /**
     * @param list<int|string> $ids
     * @return list<int>
     */
    private function positiveIds(array $ids): array
    {
        $positiveIds = [];

        foreach ($ids as $id) {
            $userId = (int) $id;

            if ($userId > 0) {
                $positiveIds[] = $userId;
            }
        }

        return $positiveIds;
    }

    /**
     * @param list<int> $ids
     * @return array<int, User>
     */
    private function usersById(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $users = User::query()
            ->with('roleRelation')
            ->whereIn('id', array_values(array_unique($ids)))
            ->get();

        $indexed = [];

        foreach ($users as $user) {
            if ($user instanceof User) {
                $indexed[(int) $user->getKey()] = $user;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{name:string,email:string,role_id:int,is_active:int}
     */
    private function normalizeUserState(array $data): array
    {
        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => User::normalizeEmail((string) ($data['email'] ?? '')),
            'role_id' => (int) ($data['role_id'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ];
    }
}
