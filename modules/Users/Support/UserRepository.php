<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Support\AdminSearch;
use App\Modules\Users\Models\User;
use Marwa\Support\Arr;
use Marwa\Support\Str;

final class UserRepository
{
    public function __construct(
        private readonly AdminSearch $search,
    ) {}

    /**
     * @return array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatedUsers(string $query, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $query = trim($query);
        $builder = User::newQuery()->getBaseBuilder()->orderBy('created_at', 'desc');

        $this->search->applyLikeFilters($builder, $query, ['name', 'email']);

        $pageData = $builder->paginate($perPage, $page);
        $pageData['data'] = array_map(
            static fn (array|object $row): User => User::newInstance(is_array($row) ? $row : (array) $row, true),
            $pageData['data']
        );

        return $pageData;
    }

    public function protectedAdminId(): int|string|null
    {
        $builder = User::newQuery()->getBaseBuilder()
            ->where('role', '=', 'admin')
            ->whereNull('deleted_at');

        if ($builder->count() !== 1) {
            return null;
        }

        $row = $builder->first();

        if ($row === null) {
            return null;
        }

        return User::newInstance(is_array($row) ? $row : (array) $row, true)->getKey();
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

    public function isLastAdminUser(User $user): bool
    {
        if ($this->normalized((string) $user->getAttribute('role')) !== 'admin') {
            return false;
        }

        return User::newQuery()->getBaseBuilder()
            ->whereNull('deleted_at')
            ->where('role', '=', 'admin')
            ->count() <= 1;
    }

    public function findDuplicateUserByEmail(string $email, ?int $ignoreId = null): ?User
    {
        $email = $this->normalizeEmail($email);

        if ($email === '') {
            return null;
        }

        $builder = User::newQuery()->getBaseBuilder()
            ->where('email', '=', $email)
            ->orderBy('created_at', 'desc');

        if ($ignoreId !== null) {
            $builder->where('id', '!=', $ignoreId);
        }

        $row = $builder->first();

        return $row === null ? null : User::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function duplicateUserMessage(User $duplicate): string
    {
        if (!empty($duplicate->getAttribute('deleted_at'))) {
            return 'Duplicate user: a trashed user already uses this email. Restore that user or choose another email.';
        }

        return 'Duplicate user: this email already belongs to another user.';
    }

    /**
     * @param array{name:string,email:string,role:string,is_active:int} $afterState
     */
    public function createUser(array $afterState, string $password): User
    {
        return User::create([
            'name' => $afterState['name'],
            'email' => $this->normalizeEmail($afterState['email']),
            'role' => $afterState['role'],
            'is_active' => $afterState['is_active'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * @param array{name:string,email:string,role:string,is_active:int} $afterState
     */
    public function updateUser(User $user, array $afterState, ?string $password = null): void
    {
        $payload = $afterState;

        if ($password !== null && $password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $payload['email'] = $this->normalizeEmail((string) $payload['email']);

        $user->forceFill($payload)->saveOrFail();
    }

    public function isSelfProtectedAdmin(User $user, array $afterState, AuthManager $auth): bool
    {
        $currentUser = $auth->user();
        $currentEmail = $currentUser instanceof User
            ? $this->normalized((string) $currentUser->getAttribute('email'))
            : '';
        $targetEmail = $this->normalized((string) $user->getAttribute('email'));

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

        $currentEmail = $this->normalized((string) $currentUser->getAttribute('email'));
        $targetEmail = $this->normalized((string) $user->getAttribute('email'));

        return ($currentEmail !== '' && $currentEmail === $targetEmail)
            || $currentUser->getKey() === $user->getKey();
    }

    public function normalizeEmail(string $email): string
    {
        return $this->normalized($email);
    }

    private function normalized(string $value): string
    {
        return Str::lower(trim($value));
    }
}
