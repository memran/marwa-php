<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Marwa\DB\ORM\QueryBuilder;

final class UserRepository
{
    /**
     * @return array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatedUsers(
        string $query = '',
        int $page = 1,
        ?int $perPage = null,
        string $sort = 'created_at',
        string $direction = 'desc',
        UserStatus $status = UserStatus::All
    ): array {
        $defaultPerPage = (int) config('pagination.default_per_page', 10);
        $configuredPerPage = config('settings.lifecycle.pagination.default_per_page', null);

        $perPage = max(1, (int) ($perPage ?? $configuredPerPage ?? $defaultPerPage));
        return $this->query($query, $sort, $direction, $status)->paginate($perPage, $page);
    }

    public function findById(int $id, bool $includeTrashed = false): ?User
    {
        if ($id <= 0) {
            return null;
        }

        $builder = $includeTrashed ? User::withTrashed() : User::query();

        return $builder->with('roleRelation')->whereKey($id)->first();
    }

    /**
     * @return list<User>
     */
    public function exportUsers(
        string $query = '',
        string $sort = 'created_at',
        string $direction = 'desc',
        UserStatus $status = UserStatus::All
    ): array {
        $rows = [];

        foreach ($this->query($query, $sort, $direction, $status)->get() as $user) {
            if ($user instanceof User) {
                $rows[] = $user;
            }
        }

        return $rows;
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

    private function query(
        string $query = '',
        string $sort = 'created_at',
        string $direction = 'desc',
        UserStatus $status = UserStatus::All
    ): QueryBuilder {
        $user = new User();
        $builder = User::query()
            ->with('roleRelation');
        $baseBuilder = $builder->getBaseBuilder();

        $user->scopeSearch($baseBuilder, $query);
        $user->scopeSort($baseBuilder, $sort, $direction);

        if ($status === UserStatus::Active) {
            $user->scopeActive($baseBuilder);

            return $builder;
        }

        if ($status === UserStatus::Disabled) {
            $user->scopeDisabled($baseBuilder);

            return $builder;
        }

        return match ($status) {
            UserStatus::Trashed => $builder->onlyTrashed(),
            default => $builder,
        };
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
