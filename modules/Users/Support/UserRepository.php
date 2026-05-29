<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\AuthManager;
use App\Support\AdminSearch;
use App\Modules\Users\Models\User;
use Marwa\Support\Arr;

final class UserRepository
{
    private ?int $adminRoleId = null;

    public function __construct(
        private readonly AdminSearch $search,
        private readonly UserActivityService $activity,
    ) {}

    private function adminRoleId(): ?int
    {
        if ($this->adminRoleId !== null) {
            return $this->adminRoleId;
        }

        $role = Role::findBySlug('admin');

        return $this->adminRoleId = $role === null ? null : (int) $role->getKey();
    }

    /**
     * @return array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatedUsers(
        string $query,
        int $page,
        ?int $perPage = null,
        string $status = 'all',
        string $sort = 'created_at',
        string $direction = 'desc'
    ): array
    {
        $page = max(1, $page);
        $perPage = max(1, (int) ($perPage ?? config('settings.lifecycle.pagination.default_per_page', config('pagination.default_per_page', 10))));
        $builder = $this->buildListingBuilder($query, $status, $sort, $direction);
        $pageData = $builder->paginate($perPage, $page);
        $pageData['data'] = $this->hydrateUsers($pageData['data']);

        return $pageData;
    }

    /**
     * @return list<User>
     */
    public function listUsers(
        string $query,
        string $status = 'all',
        string $sort = 'created_at',
        string $direction = 'desc'
    ): array {
        return $this->hydrateUsers($this->buildListingBuilder($query, $status, $sort, $direction)->get());
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
        $normalized = [];

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                continue;
            }

            $id = (int) $id;
            if ($id <= 0 || in_array($id, $normalized, true)) {
                continue;
            }

            $normalized[] = $id;
        }

        $ids = $normalized;

        if ($ids === []) {
            return [];
        }

        $users = User::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        return array_values(array_filter($users, static fn ($user): bool => $user instanceof User));
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
    public function createUser(array $afterState, string $password, ?User $actor = null): User
    {
        $user = User::create([
            'name' => $afterState['name'],
            'email' => User::normalizeEmail($afterState['email']),
            'role_id' => $afterState['role_id'],
            'is_active' => $afterState['is_active'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $user;
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     */
    public function updateUser(User $user, array $afterState, ?string $password = null, ?User $actor = null): void
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

    public function deleteUser(User $user, ?User $actor = null): void
    {
        $user->deleteOrFail();
    }

    public function restoreUser(User $user, ?User $actor = null): bool
    {
        if (!$user->restore()) {
            return false;
        }

        return true;
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

    public function isSelfProtectedAdmin(User $user, array $afterState, AuthManager $auth): bool
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

    public function isActiveSessionUser(User $user, AuthManager $auth): bool
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

    private function applyStatusFilter(object $builder, string $status): void
    {
        if ($status === 'active') {
            $builder->whereNull('deleted_at')->where('is_active', '=', 1);
            return;
        }

        if ($status === 'disabled') {
            $builder->whereNull('deleted_at')->where('is_active', '=', 0);
            return;
        }

        if ($status === 'trashed') {
            $builder->whereNotNull('deleted_at');
        }
    }

    private function applySort(object $builder, string $sort, string $direction): void
    {
        $column = match ($sort) {
            'name' => 'name',
            'email' => 'email',
            'role' => 'role_id',
            'last_login' => 'last_login_at',
            default => 'created_at',
        };

        $builder->orderBy($column, $direction);
    }

    private function buildListingBuilder(string $query, string $status, string $sort, string $direction): object
    {
        $builder = User::newQuery()->getBaseBuilder();
        $query = trim($query);
        $status = trim($status);
        $sort = trim($sort);
        $direction = strtolower(trim($direction)) === 'asc' ? 'asc' : 'desc';

        $this->search->applyLikeFilters($builder, $query, ['name', 'email']);
        $this->applyStatusFilter($builder, $status);
        $this->applySort($builder, $sort, $direction);

        return $builder;
    }

    /**
     * @param array<int, array<string, mixed>|object> $rows
     * @return list<User>
     */
    private function hydrateUsers(array $rows): array
    {
        $users = array_map(
            static fn (array|object $row): User => User::newInstance(is_array($row) ? $row : (array) $row, true),
            $rows
        );

        if ($users !== []) {
            $users[0]->roleRelation()->eagerLoad($users, 'roleRelation');
        }

        return $users;
    }

}
