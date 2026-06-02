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

        return (int) $builder->count() > 0;
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

        $count = (int) $builder->count();

        if ($count !== 1) {
            return null;
        }

        $user = $builder->orderBy('id', 'asc')->first();

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

    /**
     * @return QueryBuilder<User>
     */
    private function query(
        string $query = '',
        string $sort = 'created_at',
        string $direction = 'desc',
        UserStatus $status = UserStatus::All
    ): QueryBuilder {
        $builder = User::query()
            ->with('roleRelation')
            ->search($query)
            ->sort($sort, $direction);

        return match ($status) {
            UserStatus::Active => $builder->active(),
            UserStatus::Disabled => $builder->disabled(),
            UserStatus::Trashed => $builder->onlyTrashed(),
            default => $builder,
        };
    }

    private function findAdminRoleId(): ?int
    {
        $role = Role::findBySlug('admin');

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
