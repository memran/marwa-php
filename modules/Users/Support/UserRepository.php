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
    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->findById($userId);
            if ($user === null || $this->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $this->deleteUser($user);
            $deleted++;
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @param list<int|string> $ids
     * @return array{updated:int, skipped:int}
     */
    public function bulkStatus(array $ids, bool $isActive): array
    {
        $updated = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->findById($userId);
            if ($user === null || $this->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $user->setAttribute('is_active', $isActive ? 1 : 0);
            $user->save();
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
     * @return list<Role>
     */
    public function roles(): array
    {
        return Role::query()->orderBy('name', 'asc')->get();
    }

    private function findAdminRoleId(): ?int
    {
        $role = Role::findBy('slug', 'admin');

        return $role !== null ? (int) $role->getKey() : null;
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
